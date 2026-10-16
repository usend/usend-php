<?php

declare(strict_types=1);

namespace Usend\Resources;

/**
 * Short links on the shared tracking host that redirect to a target URL and count clicks.
 *
 *     $usend->shortLinks->create(['target_url' => 'https://acme.com/sale', 'code' => 'sale']);
 *
 * update() takes target_url, title, disabled and expires_at (null clears the expiry).
 */
final class ShortLinks extends Crud
{
    protected const PATH = '/short-links';

    /**
     * Click statistics for the last $days days (1 to 365, default 30).
     *
     * @return array<mixed>
     */
    public function stats(string $id, ?int $days = null): array
    {
        return $this->request('GET', self::PATH . '/' . $this->esc($id) . '/stats' . $this->qs(['days' => $days]));
    }
}
