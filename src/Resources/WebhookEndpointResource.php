<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Resources;

use AlfaSix\Alfakit\Actions\ActionGroup;
use AlfaSix\Alfakit\Actions\DeleteAction;
use AlfaSix\Alfakit\Actions\DeleteBulkAction;
use AlfaSix\Alfakit\Actions\EditAction;
use AlfaSix\Alfakit\Forms\Components\CheckboxList;
use AlfaSix\Alfakit\Forms\Components\KeyValue;
use AlfaSix\Alfakit\Forms\Components\Radio;
use AlfaSix\Alfakit\Forms\Components\TextInput;
use AlfaSix\Alfakit\Forms\Components\Toggle;
use AlfaSix\Alfakit\Resources\Resource;
use AlfaSix\Alfakit\Schemas\Components\Callout;
use AlfaSix\Alfakit\Schemas\Components\Section;
use AlfaSix\Alfakit\Schemas\Components\Text;
use AlfaSix\Alfakit\Schemas\Components\Utilities\Get;
use AlfaSix\Alfakit\Schemas\Schema;
use AlfaSix\Alfakit\Tables\Columns\TextColumn;
use AlfaSix\Alfakit\Tables\Filters\TernaryFilter;
use AlfaSix\Alfakit\Tables\Table;
use AlfaSix\AlfakitWebhooks\Actions\SendTestWebhookAction;
use AlfaSix\AlfakitWebhooks\Actions\ToggleWebhookEndpointAction;
use AlfaSix\AlfakitWebhooks\Models\WebhookEndpoint;
use AlfaSix\AlfakitWebhooks\Resources\WebhookEndpointResource\Pages;
use AlfaSix\AlfakitWebhooks\Resources\WebhookEndpointResource\RelationManagers\DeliveriesRelationManager;
use AlfaSix\AlfakitWebhooks\Rules\SafeWebhookUrl;
use AlfaSix\AlfakitWebhooks\Rules\ValidWebhookHeaders;
use AlfaSix\AlfakitWebhooks\Support\SecretReveal;
use AlfaSix\AlfakitWebhooks\WebhooksPlugin;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * The webhook endpoints of the panel (of the current tenant on tenancy panels): URL,
 * subscribed events, custom headers and the signing secret, with each endpoint's
 * deliveries log on its edit page.
 *
 * Extend it and pass your class to `WebhooksPlugin::make()->resourceClass(...)` to change
 * the form or the table.
 */
class WebhookEndpointResource extends Resource
{
    protected static ?string $slug = 'webhooks';

    protected static ?string $recordTitleAttribute = 'url';

    // Endpoints are tenant-owned through `tenant_type` / `tenant_id`: see getEloquentQuery().
    protected static bool $isScopedToTenant = false;

    protected static bool $isGloballySearchable = false;

    protected static bool $isDiscovered = false;

    public static function getModel(): string
    {
        return WebhookEndpoint::class;
    }

    public static function getPlugin(): WebhooksPlugin
    {
        return WebhooksPlugin::get();
    }

    public static function getModelLabel(): string
    {
        return static::getPlugin()->getLabel();
    }

    public static function getPluralModelLabel(): string
    {
        return static::getPlugin()->getPluralLabel();
    }

    public static function getNavigationLabel(): string
    {
        return static::getPlugin()->getNavigationLabel();
    }

    public static function getNavigationGroup(): ?string
    {
        return static::getPlugin()->getNavigationGroup();
    }

    public static function getNavigationIcon(): ?string
    {
        return static::getPlugin()->getNavigationIcon();
    }

    public static function getNavigationSort(): ?int
    {
        return static::getPlugin()->getNavigationSort();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::getPlugin()->shouldRegisterNavigation();
    }

    public static function getRecordTitle(?Model $record): ?string
    {
        return $record instanceof WebhookEndpoint ? $record->url : parent::getRecordTitle($record);
    }

    /**
     * The tenant new endpoints belong to: the panel's current tenant when the plugin scopes
     * to tenants.
     */
    public static function getOwningTenant(): ?Model
    {
        $panel = alfakit()->getCurrentPanel();

        if ($panel === null || ! $panel->hasTenancy() || ! static::getPlugin()->isScopedToTenant()) {
            return null;
        }

        return $panel->getTenant();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $panel = alfakit()->getCurrentPanel();

        if ($panel !== null && $panel->hasTenancy() && static::getPlugin()->isScopedToTenant()) {
            $tenant = $panel->getTenant();

            // A tenancy panel without a tenant (shouldn't happen) shows nothing.
            return $tenant === null ? $query->whereRaw('1 = 0') : $query->where(static fn (Builder $query) => WebhookEndpoint::constrainToTenant($query, $tenant));
        }

        return $query;
    }

    // ---------------------------------------------------------------- Authorisation

    public static function can(string $action, ?Model $record = null): bool
    {
        $plugin = static::getPlugin();

        if ($plugin->hasAuthorization() && ! $plugin->isAuthorized()) {
            return false;
        }

        // A record of another tenant is never accessible, whatever the policy says.
        if ($record instanceof WebhookEndpoint && ($tenant = static::getOwningTenant()) !== null && ! $record->isOwnedByTenant($tenant)) {
            return false;
        }

        return $plugin->hasAuthorization() ? true : parent::can($action, $record);
    }

    // ---------------------------------------------------------------- Form

    public static function form(Schema $schema): Schema
    {
        $plugin = static::getPlugin();
        $events = $plugin->getEvents();

        return $schema->components([
            Callout::make(__('alfakit-webhooks::webhooks.secret.reveal.heading'))
                ->description(__('alfakit-webhooks::webhooks.secret.reveal.description'))
                ->icon('key-round')
                ->warning()
                ->key('revealedSecret')
                ->footer(static fn (?Model $record): array => [
                    Text::make((string) SecretReveal::get($record instanceof WebhookEndpoint ? $record : null))
                        ->fontFamily('mono')
                        ->copyable(copyMessage: __('alfakit-webhooks::webhooks.secret.copied')),
                ])
                ->visible(static fn (?Model $record): bool => $record instanceof WebhookEndpoint && SecretReveal::get($record) !== null),

            Section::make(__('alfakit-webhooks::webhooks.sections.endpoint'))
                ->description(__('alfakit-webhooks::webhooks.sections.endpoint_description'))
                ->schema([
                    TextInput::make('url')
                        ->label(__('alfakit-webhooks::webhooks.fields.url'))
                        ->placeholder('https://example.com/webhooks')
                        ->helperText(__('alfakit-webhooks::webhooks.fields.url_help'))
                        ->url()
                        ->required()
                        ->maxLength(2048)
                        ->rule(new SafeWebhookUrl($plugin))
                        ->columnSpanFull(),
                    TextInput::make('description')
                        ->label(__('alfakit-webhooks::webhooks.fields.description'))
                        ->placeholder(__('alfakit-webhooks::webhooks.fields.description_placeholder'))
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Toggle::make('is_enabled')
                        ->label(__('alfakit-webhooks::webhooks.fields.is_enabled'))
                        ->helperText(static fn (?Model $record): ?string => $record instanceof WebhookEndpoint && $record->isAutoDisabled()
                            ? $record->disabled_reason
                            : null)
                        ->default(true),
                ]),

            Section::make(__('alfakit-webhooks::webhooks.sections.events'))
                ->description(__('alfakit-webhooks::webhooks.sections.events_description'))
                ->schema([
                    Radio::make('subscription')
                        ->hiddenLabel()
                        ->label(__('alfakit-webhooks::webhooks.sections.events'))
                        ->options([
                            'all' => __('alfakit-webhooks::webhooks.fields.all_events'),
                            'selected' => __('alfakit-webhooks::webhooks.fields.selected_events'),
                        ])
                        ->descriptions([
                            'all' => __('alfakit-webhooks::webhooks.fields.all_events_help'),
                            'selected' => __('alfakit-webhooks::webhooks.fields.selected_events_help'),
                        ])
                        ->default($events === [] ? 'all' : 'selected')
                        ->formatStateUsing(static fn (?Model $record, mixed $state): string => $record instanceof WebhookEndpoint
                            ? ($record->subscribesToAllEvents() ? 'all' : 'selected')
                            : (is_string($state) ? $state : 'selected'))
                        ->required()
                        ->in(['all', 'selected'])
                        ->live(),
                    CheckboxList::make('events')
                        ->hiddenLabel()
                        ->label(__('alfakit-webhooks::webhooks.fields.events'))
                        ->options(array_map(static fn (array $event): string => $event['label'], $events))
                        ->descriptions(array_filter(array_map(static fn (array $event): ?string => $event['description'], $events)))
                        ->formatStateUsing(static fn (mixed $state): array => array_values(array_filter((array) $state, static fn (mixed $event): bool => $event !== WebhookEndpoint::ALL_EVENTS)))
                        ->columns(2)
                        ->searchable(count($events) > 8)
                        ->bulkToggleable()
                        ->required(static fn (Get $get): bool => $get('subscription') === 'selected')
                        ->visible(static fn (Get $get): bool => $get('subscription') === 'selected'),
                ]),

            Section::make(__('alfakit-webhooks::webhooks.sections.headers'))
                ->description(__('alfakit-webhooks::webhooks.sections.headers_description'))
                ->collapsible()
                ->schema([
                    KeyValue::make('headers')
                        ->hiddenLabel()
                        ->label(__('alfakit-webhooks::webhooks.fields.headers'))
                        ->keyLabel(__('alfakit-webhooks::webhooks.fields.header_name'))
                        ->valueLabel(__('alfakit-webhooks::webhooks.fields.header_value'))
                        ->keyPlaceholder('Authorization')
                        ->addActionLabel(__('alfakit-webhooks::webhooks.fields.add_header'))
                        ->rule(new ValidWebhookHeaders($plugin)),
                ]),

            Section::make(__('alfakit-webhooks::webhooks.sections.secret'))
                ->description(__('alfakit-webhooks::webhooks.sections.secret_description'))
                ->schema([
                    Text::make(static fn (?Model $record): string => $record instanceof WebhookEndpoint ? $record->getMaskedSecret() : '')
                        ->fontFamily('mono'),
                ])
                ->visibleOn('edit'),
        ]);
    }

    /**
     * Turn the form's state into the endpoint's attributes (`subscription` → `events`).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeFormData(array $data): array
    {
        $subscription = $data['subscription'] ?? 'selected';
        unset($data['subscription']);

        $data['events'] = $subscription === 'all'
            ? [WebhookEndpoint::ALL_EVENTS]
            : array_values(array_unique(array_filter((array) ($data['events'] ?? []), is_string(...))));

        if (array_key_exists('headers', $data)) {
            $headers = [];

            foreach ((array) $data['headers'] as $name => $value) {
                if (trim((string) $name) !== '') {
                    $headers[trim((string) $name)] = is_scalar($value) ? (string) $value : '';
                }
            }

            $data['headers'] = $headers === [] ? null : $headers;
        }

        return $data;
    }

    // ---------------------------------------------------------------- Table

    public static function table(Table $table): Table
    {
        $events = static::getPlugin()->getEvents();

        return $table
            ->columns([
                TextColumn::make('url')
                    ->label(__('alfakit-webhooks::webhooks.fields.url'))
                    ->description(static fn (WebhookEndpoint $record): ?string => $record->description)
                    ->fontFamily('mono')
                    ->limit(60)
                    ->tooltip(static fn (WebhookEndpoint $record): string => $record->url)
                    ->searchable(['url', 'description'])
                    ->sortable(),
                TextColumn::make('state')
                    ->label(__('alfakit-webhooks::webhooks.fields.status'))
                    ->getStateUsing(static fn (WebhookEndpoint $record): string => $record->is_enabled ? 'enabled' : ($record->isAutoDisabled() ? 'auto_disabled' : 'disabled'))
                    ->formatStateUsing(static fn (string $state): string => __('alfakit-webhooks::webhooks.endpoint_states.'.$state))
                    ->badge()
                    ->color(static fn (string $state): string => match ($state) {
                        'enabled' => 'success',
                        'auto_disabled' => 'danger',
                        default => 'gray',
                    })
                    ->tooltip(static fn (WebhookEndpoint $record): ?string => $record->isAutoDisabled() ? $record->disabled_reason : null),
                TextColumn::make('events')
                    ->label(__('alfakit-webhooks::webhooks.fields.events'))
                    ->getStateUsing(static fn (WebhookEndpoint $record): string => $record->subscribesToAllEvents()
                        ? __('alfakit-webhooks::webhooks.fields.all_events')
                        : trans_choice('alfakit-webhooks::webhooks.fields.events_count', count($record->events), ['count' => count($record->events)]))
                    ->tooltip(static fn (WebhookEndpoint $record): ?string => $record->subscribesToAllEvents()
                        ? null
                        : implode(', ', array_map(static fn (string $event): string => $events[$event]['label'] ?? $event, $record->events)))
                    ->badge()
                    ->color('gray'),
                TextColumn::make('consecutive_failures')
                    ->label(__('alfakit-webhooks::webhooks.fields.consecutive_failures'))
                    ->numeric()
                    ->badge()
                    ->color(static fn (mixed $state): string => (int) $state > 0 ? 'warning' : 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('last_delivery_at')
                    ->label(__('alfakit-webhooks::webhooks.fields.last_delivery_at'))
                    ->since()
                    ->dateTimeTooltip()
                    ->placeholder(__('alfakit-webhooks::webhooks.fields.never'))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('alfakit-webhooks::webhooks.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_enabled')
                    ->label(__('alfakit-webhooks::webhooks.fields.is_enabled')),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    SendTestWebhookAction::make(),
                    ToggleWebhookEndpointAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([DeleteBulkAction::make()])
            ->defaultSort('created_at', 'desc')
            ->emptyStateIcon('webhook')
            ->emptyStateHeading(__('alfakit-webhooks::webhooks.empty.heading'))
            ->emptyStateDescription(__('alfakit-webhooks::webhooks.empty.description'));
    }

    public static function getRelations(): array
    {
        return [DeliveriesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWebhookEndpoints::route('/'),
            'create' => Pages\CreateWebhookEndpoint::route('/create'),
            'edit' => Pages\EditWebhookEndpoint::route('/{record}/edit'),
        ];
    }
}
