<?php

declare(strict_types=1);

use AlfaSix\Alfakit\Mail\Mails;
use AlfaSix\Alfakit\Pages\MailPreview;

uses(AlfaSix\AlfakitWebhooks\Tests\TestCase::class);

require_once __DIR__.'/helpers.php';

it('renders its mails in the AlfaKit mail theme, right to left in Arabic', function (string $key): void {
    config()->set('alfakit.mail.name', 'Acme Mail');
    config()->set('alfakit.mail.primary_color', '#0f766e');

    $definition = Mails::get($key);

    expect($definition)->not->toBeNull()
        ->and($definition?->isEditable())->toBeTrue()
        ->and($definition?->getMergeTags())->not->toBe([]);

    $ltr = MailPreview::renderMail($definition, 'en');
    $rtl = MailPreview::renderMail($definition, 'ar');
    $dark = MailPreview::renderMail($definition, 'en', dark: true);

    expect($ltr['subject'])->not->toBe('')
        ->and($ltr['html'])->toContain('Acme Mail')->toContain('#0f766e')->toContain('dir="ltr"')->toContain('lang="en"')
        ->and($rtl['html'])->toContain('Acme Mail')->toContain('dir="rtl"')->toContain('lang="ar"')
        ->and($dark['html'])->toContain('content="dark"');
})->with(['webhooks.endpoint-disabled']);

it('renders the default template of its editable mails with sample data', function (string $key): void {
    $definition = Mails::get($key);

    expect($definition)->not->toBeNull();

    $unknown = AlfaSix\Alfakit\Mail\EditableTemplates::unknownMergeTags($definition, $definition->getDefaultSubject(), $definition->getDefaultBody());
    $rendered = AlfaSix\Alfakit\Mail\EditableTemplates::render($key, $definition->getDefaultSubject(), (string) $definition->getDefaultBody(), $definition->getSampleData());

    expect($unknown)->toBe([])
        ->and($rendered->subject)->not->toContain('{{')
        ->and($rendered->html->toHtml())->not->toContain('data-type="mergeTag"');
})->with(['webhooks.endpoint-disabled']);

it('emails the notifyOnDisable() users when mailOnDisable() is on', function (): void {
    Illuminate\Support\Facades\Notification::fake();
    Illuminate\Support\Facades\Http::fake(['*' => Illuminate\Support\Facades\Http::response('nope', 503)]);

    $admin = AlfaSix\AlfakitWebhooks\Tests\user();
    AlfaSix\AlfakitWebhooks\Tests\endpoint(['created_by_type' => $admin->getMorphClass(), 'created_by_id' => (string) $admin->id]);
    AlfaSix\AlfakitWebhooks\Tests\plugin()->retries(0)->disableAfterConsecutiveFailures(2)->mailOnDisable();

    foreach (range(1, 2) as $i) {
        AlfaSix\AlfakitWebhooks\Webhooks::dispatch('order.paid', ['id' => $i]);
    }

    Illuminate\Support\Facades\Notification::assertSentTo($admin, AlfaSix\AlfakitWebhooks\Notifications\EndpointDisabledMail::class, function (AlfaSix\AlfakitWebhooks\Notifications\EndpointDisabledMail $mail) use ($admin): bool {
        return $mail->failures === 2 && str_contains((string) $mail->toMail($admin)->render(), 'hooks.example.com');
    });
});

it('only stores a database notification without mailOnDisable()', function (): void {
    Illuminate\Support\Facades\Notification::fake();
    Illuminate\Support\Facades\Http::fake(['*' => Illuminate\Support\Facades\Http::response('nope', 503)]);

    $admin = AlfaSix\AlfakitWebhooks\Tests\user();
    AlfaSix\AlfakitWebhooks\Tests\endpoint(['created_by_type' => $admin->getMorphClass(), 'created_by_id' => (string) $admin->id]);
    AlfaSix\AlfakitWebhooks\Tests\plugin()->retries(0)->disableAfterConsecutiveFailures(2);

    foreach (range(1, 2) as $i) {
        AlfaSix\AlfakitWebhooks\Webhooks::dispatch('order.paid', ['id' => $i]);
    }

    Illuminate\Support\Facades\Notification::assertNotSentTo($admin, AlfaSix\AlfakitWebhooks\Notifications\EndpointDisabledMail::class);
});
