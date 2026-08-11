<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use App\Traits\HasCreatorUpdater;

class WebsiteEnquiry extends Model
{
    use SoftDeletes, LogsActivity, HasCreatorUpdater;

    protected $table = 'website_enquiries';

    // Activity log module name override
    public string $activityModule = 'website_enquiry';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'status',
        'admin_remark',
        'resolved_at',
        'resolved_by',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    // Status Constants
    public const STATUS_NEW = 'New';
    public const STATUS_IN_PROGRESS = 'In Progress';
    public const STATUS_CONTACTED = 'Contacted';
    public const STATUS_RESOLVED = 'Resolved';
    public const STATUS_CLOSED = 'Closed';

    /**
     * Get list of all valid statuses
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_NEW,
            self::STATUS_IN_PROGRESS,
            self::STATUS_CONTACTED,
            self::STATUS_RESOLVED,
            self::STATUS_CLOSED,
        ];
    }

    /**
     * Get the user who resolved the enquiry.
     */
    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Get the user who created this record.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Get the user who updated this record.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
