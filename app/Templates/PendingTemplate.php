<?php

namespace App\Templates;

use App\Models\User;
use App\Models\Workflow;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Session;

final class PendingTemplate
{
    private const SESSION_KEY = 'pending_template_slug';

    public function __construct(
        private readonly TemplateInstaller $installer,
    ) {}

    public function remember(string $slug): void
    {
        if (TemplateRepository::find($slug) === null) {
            return;
        }

        Session::put(self::SESSION_KEY, $slug);
    }

    public function currentSlug(): ?string
    {
        $slug = Session::get(self::SESSION_KEY);

        return is_string($slug) && TemplateRepository::find($slug) !== null ? $slug : null;
    }

    public function consume(User $user): ?Workflow
    {
        $slug = Session::pull(self::SESSION_KEY);

        if (! is_string($slug) || $slug === '') {
            return null;
        }

        $template = TemplateRepository::find($slug);

        if ($template === null) {
            return null;
        }

        try {
            return $this->installer->install($user, $template);
        } catch (AuthorizationException $e) {
            Session::flash('template_error', $e->getMessage());

            return null;
        }
    }
}
