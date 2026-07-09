<?php

namespace App\Models;

use App\Domain\Workshops\Enums\WorkshopUserRole;
use Database\Factories\WorkshopUserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkshopUser extends Model
{
    /** @use HasFactory<WorkshopUserFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'workshop_id',
        'user_id',
        'role',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => WorkshopUserRole::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Workshop, $this> */
    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    public function isLastOwner(): bool
    {
        if ($this->role !== WorkshopUserRole::Owner) {
            return false;
        }

        return self::query()
            ->where('workshop_id', $this->workshop_id)
            ->where('role', WorkshopUserRole::Owner)
            ->whereKeyNot($this->id)
            ->doesntExist();
    }
}
