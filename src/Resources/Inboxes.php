<?php

declare(strict_types=1);

namespace Usend\Resources;

/** Shared mailboxes with threads, labels, drafts and replies (full-access keys only). */
final class Inboxes extends Crud
{
    protected const PATH = '/inboxes';

    private function p(string $inboxId, string $rest = ''): string
    {
        return '/inboxes/' . $this->esc($inboxId) . $rest;
    }

    /** @param array<string,mixed> $body @return array<mixed> */
    public function createLabel(string $inboxId, array $body): array
    {
        return $this->request('POST', $this->p($inboxId, '/labels'), $body);
    }

    /** @return array<mixed> */
    public function labels(string $inboxId): array
    {
        return $this->request('GET', $this->p($inboxId, '/labels'));
    }

    /** @return array<mixed> */
    public function label(string $inboxId, string $labelId): array
    {
        return $this->request('GET', $this->p($inboxId, '/labels/' . $this->esc($labelId)));
    }

    /** @param array<string,mixed> $body @return array<mixed> */
    public function updateLabel(string $inboxId, string $labelId, array $body): array
    {
        return $this->request('PATCH', $this->p($inboxId, '/labels/' . $this->esc($labelId)), $body);
    }

    /** @return array<mixed> */
    public function removeLabel(string $inboxId, string $labelId): array
    {
        return $this->request('DELETE', $this->p($inboxId, '/labels/' . $this->esc($labelId)));
    }

    /** @param array<string,mixed> $params folder, label, assignee, unread, q, limit, after, before @return array<mixed> */
    public function threads(string $inboxId, array $params = []): array
    {
        return $this->request('GET', $this->p($inboxId, '/threads') . $this->qs($params));
    }

    /** @return array<mixed> */
    public function thread(string $inboxId, string $threadId): array
    {
        return $this->request('GET', $this->p($inboxId, '/threads/' . $this->esc($threadId)));
    }

    /** @param array<string,mixed> $body folder, labels, assignee_user_id, read @return array<mixed> */
    public function updateThread(string $inboxId, string $threadId, array $body): array
    {
        return $this->request('PATCH', $this->p($inboxId, '/threads/' . $this->esc($threadId)), $body);
    }

    /** @return array<mixed> */
    public function removeThread(string $inboxId, string $threadId): array
    {
        return $this->request('DELETE', $this->p($inboxId, '/threads/' . $this->esc($threadId)));
    }

    /** @param array<string,mixed> $params @return array<mixed> */
    public function threadEmails(string $inboxId, string $threadId, array $params = []): array
    {
        return $this->request('GET', $this->p($inboxId, '/threads/' . $this->esc($threadId) . '/emails') . $this->qs($params));
    }

    /** @return array<mixed> */
    public function threadEmail(string $inboxId, string $threadId, string $emailId): array
    {
        return $this->request('GET', $this->p($inboxId, '/threads/' . $this->esc($threadId) . '/emails/' . $this->esc($emailId)));
    }

    /** @param array<string,mixed> $body text, html, to, cc, bcc, subject, reply_all @return array<mixed> */
    public function reply(string $inboxId, string $threadId, string $emailId, array $body): array
    {
        return $this->request('POST', $this->p($inboxId, '/threads/' . $this->esc($threadId) . '/emails/' . $this->esc($emailId) . '/reply'), $body);
    }

    /** @param array<string,mixed> $body to, text, html, cc, bcc, subject @return array<mixed> */
    public function forward(string $inboxId, string $threadId, string $emailId, array $body): array
    {
        return $this->request('POST', $this->p($inboxId, '/threads/' . $this->esc($threadId) . '/emails/' . $this->esc($emailId) . '/forward'), $body);
    }

    /** @param array<string,mixed> $body @return array<mixed> */
    public function createDraft(string $inboxId, array $body): array
    {
        return $this->request('POST', $this->p($inboxId, '/drafts'), $body);
    }

    /** @param array<string,mixed> $params @return array<mixed> */
    public function drafts(string $inboxId, array $params = []): array
    {
        return $this->request('GET', $this->p($inboxId, '/drafts') . $this->qs($params));
    }

    /** @return array<mixed> */
    public function draft(string $inboxId, string $draftId): array
    {
        return $this->request('GET', $this->p($inboxId, '/drafts/' . $this->esc($draftId)));
    }

    /** @param array<string,mixed> $body @return array<mixed> */
    public function updateDraft(string $inboxId, string $draftId, array $body): array
    {
        return $this->request('PATCH', $this->p($inboxId, '/drafts/' . $this->esc($draftId)), $body);
    }

    /** @return array<mixed> */
    public function removeDraft(string $inboxId, string $draftId): array
    {
        return $this->request('DELETE', $this->p($inboxId, '/drafts/' . $this->esc($draftId)));
    }

    /** Sends the draft and deletes it. @return array<mixed> */
    public function sendDraft(string $inboxId, string $draftId): array
    {
        return $this->request('POST', $this->p($inboxId, '/drafts/' . $this->esc($draftId) . '/send'));
    }
}
