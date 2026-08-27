<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use App\Traits\HasCreatorUpdater;

class GoldInspectionEnquiry extends Model
{
    use SoftDeletes, LogsActivity, HasCreatorUpdater;

    protected $table = 'gold_inspection_enquiries';

    // Activity log module name override
    public string $activityModule = 'inspection';

    protected $fillable = [
        'name',
        'phone',
        'address',
        'approx_grams',
        'gold_type',
        'gold_location',
        'preferred_date',
        'photos',
        'status',
        'admin_remark',
        'reviewed_by',
        'reviewed_at',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'approx_grams' => 'decimal:2',
        'preferred_date' => 'date',
        'photos' => 'array',
        'reviewed_at' => 'datetime',
    ];

    // Status Constants
    public const STATUS_NEW = 'New';
    public const STATUS_CONTACTED = 'Contacted';
    public const STATUS_INSPECTION_SCHEDULED = 'Inspection Scheduled';
    public const STATUS_INSPECTION_COMPLETED = 'Inspection Completed';
    public const STATUS_CONVERTED = 'Converted';
    public const STATUS_REJECTED = 'Rejected';

    /**
     * Get list of all valid statuses
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_NEW,
            self::STATUS_CONTACTED,
            self::STATUS_INSPECTION_SCHEDULED,
            self::STATUS_INSPECTION_COMPLETED,
            self::STATUS_CONVERTED,
            self::STATUS_REJECTED,
        ];
    }

    /**
     * Get the user who reviewed/assigned the status transition.
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
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

    /**
     * Get photos URLs.
     */
    public function getPhotoUrlsAttribute()
    {
        if (!$this->photos || !is_array($this->photos)) {
            return [];
        }

        return array_map(function ($img) {
            return storage_url($img);
        }, $this->photos);
    }
}
