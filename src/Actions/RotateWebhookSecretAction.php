<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Actions;

use AlfaSix\Alfakit\Actions\Action;
use AlfaSix\Alfakit\Forms\Components\Select;
use AlfaSix\Alfakit\Notifications\Notification;
use AlfaSix\AlfakitWebhooks\Models\WebhookEndpoint;
use AlfaSix\AlfakitWebhooks\Support\SecretReveal;
use AlfaSix\AlfakitWebhooks\WebhooksPlugin;
use Illuminate\Database\Eloquent\Model;

/**
 * Generates a new signing secret, shown once on the page that follows. The old secret can
 * keep signing (as a second `v1` signature) for a while, so receivers switch without
 * dropping events.
 */
class RotateWebhookSecretAction extends Action
{
    /** @var array<int, int> hours the previous secret may stay valid */
    public const GRACE_PERIODS = [0, 1, 24, 168];

    public static function make(string $name = 'rotateWebhookSecret'): static
    {
        return parent::make($name);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('alfakit-webhooks::webhooks.actions.rotate.label'));
        $this->icon('key-round');
        $this->color('warning');
        $this->modalIcon('key-round');
        $this->modalHeading(__('alfakit-webhooks::webhooks.actions.rotate.modal.heading'));
        $this->modalDescription(__('alfakit-webhooks::webhooks.actions.rotate.modal.description'));
        $this->modalSubmitActionLabel(__('alfakit-webhooks::webhooks.actions.rotate.modal.submit'));
        $this->modalWidth('md');

        $this->schema([
            Select::make('keep_previous_for')
                ->label(__('alfakit-webhooks::webhooks.actions.rotate.keep_previous'))
                ->options(collect(self::GRACE_PERIODS)->mapWithKeys(static fn (int $hours): array => [
                    (string) $hours => $hours === 0
                        ? __('alfakit-webhooks::webhooks.actions.rotate.grace.none')
                        : trans_choice('alfakit-webhooks::webhooks.actions.rotate.grace.hours', $hours, ['count' => $hours]),
                ])->all())
                ->default('24')
                ->required()
                ->selectablePlaceholder(false)
                ->in(array_map('strval', self::GRACE_PERIODS)),
        ]);

        $this->authorize(static fn (?Model $record): bool => $record instanceof WebhookEndpoint && WebhooksPlugin::get()->getResourceClass()::canEdit($record));

        $this->action(static function (Model $record, array $data): void {
            if (! $record instanceof WebhookEndpoint) {
                return;
            }

            $hours = (int) ($data['keep_previous_for'] ?? 0);
            $secret = $record->rotateSecret($hours > 0 ? now()->addHours($hours) : null);

            SecretReveal::flash($record, $secret);

            Notification::make()
                ->success()
                ->title(__('alfakit-webhooks::webhooks.actions.rotate.notification'))
                ->send();
        });
    }
}
