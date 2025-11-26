<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use App\Traits\BaseModelLoggingTrait;
use App\Models\FormFieldsData\CancellationPolicies;
use App\Models\FormFieldsData\AvailableAmenties;

class InquiryQuoteDetails extends Model
{
    use BaseModelLoggingTrait;
    protected $table = 'inquiry_quote_details';
    protected $fillable = [
        'booking_inquiries_id',
        'client_id',
        'aircraft_id',
        'estimated_flight_time',
        'base_fare',
        'fluel_cost',
        'taxes_fees',
        'crew_fees',
        'handling_fees',
        'catering_fees',
        'total',
        'validate_till',
        'cancellation_policy_id',
        'special_offers_promotions',
        'additional_notes',
        'amenities_ids',
        'is_selected',
        'rejected_reason',
        'kx_mgr_commission_per',
        'travel_agent_commission_per',

    ];

    // Optionally include computed amenities in JSON
    protected $appends = ['available_amenities'];

    public function client(){
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function aircraft(){
        return $this->belongsTo(Asset::class, 'aircraft_id');
    }

    public function cancelationPolicy(){
        return $this->belongsTo(CancellationPolicies::class, 'cancellation_policy_id');
    }

    // Accessor to resolve comma-separated IDs into models
    public function getAvailableAmenitiesAttribute(): Collection
    {
        if (empty($this->amenities_ids)) {
            return collect();
        }
        $ids = collect(explode(',', $this->amenities_ids))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }
        return AvailableAmenties::whereIn('id', $ids)->get();
    }

    public function scopeWithRelations($query)
    {
        return $query->with([
            'client',
            'aircraft',
            'cancelationPolicy',
        ]);
    }
}
