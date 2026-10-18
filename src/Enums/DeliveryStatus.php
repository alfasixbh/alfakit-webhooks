<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitWebhooks\Enums;

use AlfaSix\Alfakit\Support\Contracts\HasColor;
use AlfaSix\Alfakit\Support\Contracts\HasIcon;
use AlfaSix\Alfakit\Support\Contracts\HasLabel;

/**
 * The state of one delivery attempt.
 */
enum DeliveryStatus: string implements HasColor, HasIcon, HasLabel
{
    /** Queued, not sent yet. */
    case Pending = 'pending';

    /** The endpoint answered with a 2xx status. */
    case Succeeded = 'succeeded';

    /** The request failed (non-2xx status, timeout, connection error or blocked URL). */
    case Failed = 'failed';

    /** Not sent: the endpoint was disabled or deleted before the attempt ran. */
    case Skipped = 'skipped';

    public function getLabel(): string
    {
        return (string) __('alfakit-webhooks::webhooks.statuses.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Succeeded => 'success',
            self::Failed => 'danger',
            self::Skipped => 'warning',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Pending => 'clock',
            self::Succeeded => 'circle-check',
            self::Failed => 'circle-x',
            self::Skipped => 'circle-slash',
        };
    }
}
