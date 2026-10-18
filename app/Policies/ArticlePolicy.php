<?php

namespace App\Policies;

use App\Enums\ArticleAction;
use App\Enums\ArticleStatus;
use App\Enums\PermissionName;
use App\Models\Article;
use App\Models\User;

/**
 * Otorisasi artikel dan alur persetujuannya.
 *
 * - Kontributor hanya menyentuh artikel miliknya selagi draf atau perlu revisi (anti-IDOR, A01).
 * - Editor mereview (setujui / minta revisi), Approver menerbitkan dan mengarsipkan.
 * - Prinsip empat mata (A06): penulis tidak boleh menyetujui atau menerbitkan artikelnya sendiri.
 *
 * Nama ability aksi alur sama dengan ArticleAction::ability(). Super Admin
 * melewati semua ability lewat Gate::before; aturan status tetap dijaga ArticleWorkflow.
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

    /**
     * Penulis mengajukan artikelnya; editor boleh mengajukan atas nama penulis.
     */
    public function submit(User $user, Article $article): bool
    {
        if (! ArticleAction::Submit->allowsFrom($article->status)) {
            return false;
        }

        return $this->owns($user, $article)
            ? $user->can(PermissionName::ArticlesCreate)
            : $user->can(PermissionName::ArticlesUpdateAny);
    }

    public function approve(User $user, Article $article): bool
    {
        return ArticleAction::Approve->allowsFrom($article->status)
            && $this->canReview($user)
            && ! $this->owns($user, $article);
    }

    public function requestRevision(User $user, Article $article): bool
    {
        return ArticleAction::RequestRevision->allowsFrom($article->status)
            && $this->canReview($user);
    }

    public function publish(User $user, Article $article): bool
    {
        return ArticleAction::Publish->allowsFrom($article->status)
            && $user->can(PermissionName::ArticlesPublish)
            && ! $this->owns($user, $article);
    }

    public function archive(User $user, Article $article): bool
    {
        return ArticleAction::Archive->allowsFrom($article->status)
            && $user->can(PermissionName::ArticlesPublish);
    }

    public function unarchive(User $user, Article $article): bool
    {
        return ArticleAction::Unarchive->allowsFrom($article->status)
            && $user->can(PermissionName::ArticlesPublish);
    }

    /**
     * Siapa pun yang boleh melihat artikel boleh berdiskusi di riwayatnya.
     */
    public function comment(User $user, Article $article): bool
    {
        return $this->view($user, $article);
    }

    /**
     * Editor mereview; Approver juga boleh karena berada satu tingkat di atasnya.
     */
    private function canReview(User $user): bool
    {
        return $user->can(PermissionName::ArticlesReview) || $user->can(PermissionName::ArticlesPublish);
    }

    private function owns(User $user, Article $article): bool
    {
        return $article->author_id === $user->id;
    }
}
