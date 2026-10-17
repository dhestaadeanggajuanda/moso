<?php

namespace App\Policies;

use App\Enums\ArticleStatus;
use App\Enums\PermissionName;
use App\Models\Article;
use App\Models\User;

/**
 * Otorisasi dasar artikel. Transisi status (review/terbit) ditambahkan di Fase 5.
 *
 * Kontributor hanya boleh menyentuh artikel miliknya sendiri yang masih
 * berstatus draft atau perlu revisi (anti-IDOR, OWASP A01).
 */
class ArticlePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::ArticlesViewAny);
    }

    public function view(User $user, Article $article): bool
    {
        return $user->can(PermissionName::ArticlesUpdateAny) || $this->owns($user, $article);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::ArticlesCreate);
    }

    public function update(User $user, Article $article): bool
    {
        if ($user->can(PermissionName::ArticlesUpdateAny)) {
            return true;
        }

        return $user->can(PermissionName::ArticlesUpdateOwn)
            && $this->owns($user, $article)
            && $article->status->isEditableByAuthor();
    }

    public function delete(User $user, Article $article): bool
    {
        if ($user->can(PermissionName::ArticlesDeleteAny)) {
            return true;
        }

        return $this->owns($user, $article) && $article->status === ArticleStatus::Draft;
    }

    public function restore(User $user, Article $article): bool
    {
        return $user->can(PermissionName::ArticlesDeleteAny);
    }

    public function forceDelete(User $user, Article $article): bool
    {
        return false;
    }

    private function owns(User $user, Article $article): bool
    {
        return $article->author_id === $user->id;
    }
}
