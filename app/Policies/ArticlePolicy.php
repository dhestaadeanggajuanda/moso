<?php

namespace App\Policies;

use App\Enums\ArticleAction;
use App\Enums\ArticleStatus;
use App\Enums\ArticleWorkflowMode;
use App\Enums\PermissionName;
use App\Models\Article;
use App\Models\User;

/**
 * Otorisasi artikel dan alur penerbitannya, mengikuti ArticleWorkflowMode::current().
 *
 * Alur Sederhana (bawaan, seperti WordPress):
 * - Editor dan Approver menulis, mengubah, dan menerbitkan semua artikel, termasuk miliknya.
 * - Penulis menerbitkan dan mengubah artikelnya sendiri.
 * - Kontributor hanya menyentuh artikel miliknya selagi draf atau perlu revisi; Editor menerbitkannya.
 *
 * Alur Bertahap (empat mata, A06):
 * - Editor mereview (setujui / minta revisi), Approver menerbitkan dan mengarsipkan.
 * - Penulis tidak boleh menyetujui atau menerbitkan artikelnya sendiri.
 *
 * Kepemilikan selalu dicek di sini (anti-IDOR, A01). Nama ability aksi alur sama
 * dengan ArticleAction::ability(). Super Admin melewati semua ability lewat
 * Gate::before; aturan status tetap dijaga ArticleWorkflow.
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

    /**
     * Tombol "Terbitkan" di form artikel baru: penulisnya adalah pengguna ini.
     */
    public function publishOwnNew(User $user): bool
    {
        return $this->simple()
            && $user->can(PermissionName::ArticlesCreate)
            && ($this->publishesAny($user) || $user->can(PermissionName::ArticlesPublishOwn));
    }

    public function update(User $user, Article $article): bool
    {
        if ($article->status->requiresPublisher()) {
            return $this->simple()
                ? $this->publishesAny($user) || $this->publishesOwn($user, $article)
                // Isi yang sudah tayang hanya diubah pemegang hak terbitkan, dan bukan penulisnya sendiri.
                : $user->can(PermissionName::ArticlesPublish) && ! $this->owns($user, $article);
        }

        if ($user->can(PermissionName::ArticlesUpdateAny) || $this->publishesOwn($user, $article)) {
            return true;
        }

        return $user->can(PermissionName::ArticlesUpdateOwn)
            && $this->owns($user, $article)
            && $article->status->isEditableByAuthor();
    }

    public function delete(User $user, Article $article): bool
    {
        // Menghapus artikel terbit sama dengan menurunkannya dari situs: setara mengarsipkan.
        if ($article->status->requiresPublisher()) {
            return $this->simple()
                ? $this->publishesAny($user) || $this->publishesOwn($user, $article)
                : $user->can(PermissionName::ArticlesPublish);
        }

        if ($user->can(PermissionName::ArticlesDeleteAny) || $this->publishesOwn($user, $article)) {
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
     * Di alur Sederhana, yang bisa langsung menerbitkan tidak perlu mengajukan.
     */
    public function submit(User $user, Article $article): bool
    {
        if (! ArticleAction::Submit->allowsFrom($article->status)) {
            return false;
        }

        if ($this->simple() && $this->publish($user, $article)) {
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
            && $this->canReview($user)
            && ! ($this->simple() && $this->owns($user, $article));
    }

    public function publish(User $user, Article $article): bool
    {
        if (! ArticleAction::Publish->allowsFrom($article->status)) {
            return false;
        }

        return $this->simple()
            ? $this->publishesAny($user) || $this->publishesOwn($user, $article)
            : $user->can(PermissionName::ArticlesPublish) && ! $this->owns($user, $article);
    }

    public function archive(User $user, Article $article): bool
    {
        return ArticleAction::Archive->allowsFrom($article->status) && $this->canTakeDown($user, $article);
    }

    public function unarchive(User $user, Article $article): bool
    {
        return ArticleAction::Unarchive->allowsFrom($article->status) && $this->canTakeDown($user, $article);
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

    private function canTakeDown(User $user, Article $article): bool
    {
        return $user->can(PermissionName::ArticlesPublish)
            || ($this->simple() && ($this->publishesAny($user) || $this->publishesOwn($user, $article)));
    }

    /**
     * Alur Sederhana: Editor dan Approver menerbitkan artikel siapa pun.
     */
    private function publishesAny(User $user): bool
    {
        return $user->can(PermissionName::ArticlesPublish) || $user->can(PermissionName::ArticlesUpdateAny);
    }

    /**
     * Alur Sederhana: Penulis menerbitkan artikelnya sendiri.
     */
    private function publishesOwn(User $user, Article $article): bool
    {
        return $this->simple()
            && $this->owns($user, $article)
            && $user->can(PermissionName::ArticlesPublishOwn);
    }

    private function simple(): bool
    {
        return ArticleWorkflowMode::current() === ArticleWorkflowMode::Simple;
    }

    private function owns(User $user, Article $article): bool
    {
        return $article->author_id === $user->id;
    }
}
