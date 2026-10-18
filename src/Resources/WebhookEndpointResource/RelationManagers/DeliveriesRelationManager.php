<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Resources\WebhookEndpointResource\RelationManagers;

use AlfaSix\Alfakit\Actions\ViewAction;
use AlfaSix\Alfakit\Forms\Components\CodeEditor\Enums\Language;
use AlfaSix\Alfakit\Infolists\Components\CodeEntry;
use AlfaSix\Alfakit\Infolists\Components\KeyValueEntry;
use AlfaSix\Alfakit\Infolists\Components\TextEntry;
use AlfaSix\Alfakit\Resources\RelationManagers\RelationManager;
use AlfaSix\Alfakit\Schemas\Components\Grid;
use AlfaSix\Alfakit\Schemas\Components\Section;
use AlfaSix\Alfakit\Schemas\Components\Tabs;
use AlfaSix\Alfakit\Schemas\Components\Tabs\Tab;
use AlfaSix\Alfakit\Tables\Columns\TextColumn;
use AlfaSix\Alfakit\Tables\Filters\SelectFilter;
use AlfaSix\Alfakit\Tables\Filters\TernaryFilter;
use AlfaSix\Alfakit\Tables\Table;
use AlfaSix\AlfakitWebhooks\Actions\ReplayWebhookDeliveriesBulkAction;
use AlfaSix\AlfakitWebhooks\Actions\ReplayWebhookDeliveryAction;
use AlfaSix\AlfakitWebhooks\Enums\DeliveryStatus;
use AlfaSix\AlfakitWebhooks\Models\WebhookDelivery;
use AlfaSix\AlfakitWebhooks\WebhooksPlugin;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * The endpoint's deliveries log, newest first: one row per attempt with its status,
 * response code, duration and attempt number; the redacted request and response in a
 * slide-over; replay for one delivery or a selection.
 */
class DeliveriesRelationManager extends RelationManager
{
    protected static string $relationship = 'deliveries';

    protected static ?string $icon = 'history';

    protected static bool $isLazy = false;

    public static function getTitle(): string
    {
        return static::$title ?? (string) __('alfakit-webhooks::webhooks.deliveries.title');
    }

    public function getModelLabel(): string
    {
        return (string) __('alfakit-webhooks::webhooks.deliveries.label');
    }

    public function getPluralModelLabel(): string
    {
        return (string) __('alfakit-webhooks::webhooks.deliveries.plural_label');
    }

    public function getRecordTitle(?Model $record): ?string
    {
        return $record instanceof WebhookDelivery ? $record->event?->name.' #'.$record->attempt : null;
    }

    public function table(Table $table): Table
    {
        $events = WebhooksPlugin::get()->getEvents();

        return $table
            ->modifyQueryUsing(static fn (Builder $query) => $query->with(['event', 'endpoint']))
            ->columns([
                TextColumn::make('event.name')
                    ->label(__('alfakit-webhooks::webhooks.deliveries.fields.event'))
                    ->badge()
                    ->color('gray')
                    ->fontFamily('mono')
                    ->description(static fn (WebhookDelivery $record): ?string => $record->is_test ? __('alfakit-webhooks::webhooks.deliveries.test') : null),
                TextColumn::make('status')
                    ->label(__('alfakit-webhooks::webhooks.deliveries.fields.status'))
                    ->badge(),
                TextColumn::make('response_status')
                    ->label(__('alfakit-webhooks::webhooks.deliveries.fields.response_status'))
                    ->fontFamily('mono')
                    ->badge()
                    ->color(static fn (mixed $state): string => match (true) {
                        ! is_numeric($state) => 'gray',
                        (int) $state < 300 => 'success',
                        (int) $state < 500 => 'warning',
                        default => 'danger',
                    })
                    ->placeholder('—'),
                TextColumn::make('attempt')
                    ->label(__('alfakit-webhooks::webhooks.deliveries.fields.attempt'))
                    ->formatStateUsing(static fn (mixed $state): string => '#'.(int) $state)
                    ->description(static fn (WebhookDelivery $record): ?string => $record->replay_of_id !== null ? __('alfakit-webhooks::webhooks.deliveries.replay') : null),
                TextColumn::make('duration_ms')
                    ->label(__('alfakit-webhooks::webhooks.deliveries.fields.duration'))
                    ->numeric()
                    ->suffix(' ms')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('error')
                    ->label(__('alfakit-webhooks::webhooks.deliveries.fields.error'))
                    ->limit(60)
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('event_id')
                    ->label(__('alfakit-webhooks::webhooks.deliveries.fields.event_id'))
                    ->fontFamily('mono')
                    ->copyable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('alfakit-webhooks::webhooks.deliveries.fields.created_at'))
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('alfakit-webhooks::webhooks.deliveries.fields.status'))
                    ->options(DeliveryStatus::class)
                    ->multiple(),
                SelectFilter::make('event')
                    ->label(__('alfakit-webhooks::webhooks.deliveries.fields.event'))
                    ->options([
                        WebhooksPlugin::PING_EVENT => __('alfakit-webhooks::webhooks.deliveries.ping'),
                        ...array_map(static fn (array $event): string => $event['label'], $events),
                    ])
                    ->query(static fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('event', static fn (Builder $query) => $query->where('name', $data['value']))
                        : $query),
                TernaryFilter::make('is_test')
                    ->label(__('alfakit-webhooks::webhooks.deliveries.fields.is_test')),
            ])
            ->recordActions([
                ViewAction::make()
                    ->slideOver()
                    ->modalHeading(static fn (?Model $record): string => $record instanceof WebhookDelivery ? (string) $record->event?->name : '')
                    ->modalContent(static fn (): array => static::deliveryDetails()),
                ReplayWebhookDeliveryAction::make()->iconButton()->tooltip(__('alfakit-webhooks::webhooks.actions.replay.label')),
            ])
            ->bulkActions([ReplayWebhookDeliveriesBulkAction::make()])
            ->defaultSort('id', 'desc')
            ->poll('15s')
            ->emptyStateIcon('send')
            ->emptyStateHeading(__('alfakit-webhooks::webhooks.deliveries.empty.heading'))
            ->emptyStateDescription(__('alfakit-webhooks::webhooks.deliveries.empty.description'));
    }

    /**
     * The delivery's details: summary, then the request and the response in tabs.
     *
     * @return array<int, \AlfaSix\Alfakit\Schemas\Components\Component>
     */
    public static function deliveryDetails(): array
    {
        return [
            Section::make()->schema([
                Grid::make(2)->schema([
                    TextEntry::make('status')->label(__('alfakit-webhooks::webhooks.deliveries.fields.status'))->badge(),
                    TextEntry::make('response_status')->label(__('alfakit-webhooks::webhooks.deliveries.fields.response_status'))->fontFamily('mono')->placeholder('—'),
                    TextEntry::make('attempt')->label(__('alfakit-webhooks::webhooks.deliveries.fields.attempt'))->formatStateUsing(static fn (mixed $state): string => '#'.(int) $state),
                    TextEntry::make('duration_ms')->label(__('alfakit-webhooks::webhooks.deliveries.fields.duration'))->numeric()->suffix(' ms')->placeholder('—'),
                    TextEntry::make('event_id')->label(__('alfakit-webhooks::webhooks.deliveries.fields.event_id'))->fontFamily('mono')->copyable(),
                    TextEntry::make('sent_at')->label(__('alfakit-webhooks::webhooks.deliveries.fields.sent_at'))->dateTime()->placeholder('—'),
                    TextEntry::make('next_retry_at')->label(__('alfakit-webhooks::webhooks.deliveries.fields.next_retry_at'))->dateTime()->placeholder('—'),
                    TextEntry::make('url')->label(__('alfakit-webhooks::webhooks.fields.url'))->fontFamily('mono')->columnSpanFull(),
                    TextEntry::make('error')->label(__('alfakit-webhooks::webhooks.deliveries.fields.error'))->color('danger')->placeholder('—')->columnSpanFull(),
                ]),
            ]),
            Tabs::make()->tabs([
                Tab::make(__('alfakit-webhooks::webhooks.deliveries.request'))->schema([
                    KeyValueEntry::make('request_headers')
                        ->label(__('alfakit-webhooks::webhooks.deliveries.fields.headers'))
                        ->keyLabel(__('alfakit-webhooks::webhooks.fields.header_name'))
                        ->valueLabel(__('alfakit-webhooks::webhooks.fields.header_value')),
                    CodeEntry::make('request_body')
                        ->label(__('alfakit-webhooks::webhooks.deliveries.fields.body'))
                        ->grammar(Language::Json)
                        ->copyable(),
                ]),
                Tab::make(__('alfakit-webhooks::webhooks.deliveries.response'))->schema([
                    KeyValueEntry::make('response_headers')
                        ->label(__('alfakit-webhooks::webhooks.deliveries.fields.headers'))
                        ->keyLabel(__('alfakit-webhooks::webhooks.fields.header_name'))
                        ->valueLabel(__('alfakit-webhooks::webhooks.fields.header_value'))
                        ->placeholder('—'),
                    CodeEntry::make('response_body')
                        ->label(__('alfakit-webhooks::webhooks.deliveries.fields.body'))
                        ->copyable()
                        ->placeholder('—'),
                ]),
            ]),
        ];
    }
}
