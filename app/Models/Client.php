<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BaseModelLoggingTrait;
use App\Models\BookingInquiries\BookingInquiryOperatorAssignment;
use App\Models\FormFieldsData\AirportCities;
use App\Models\Asset;

class Client extends Model
{
    use BaseModelLoggingTrait;
    protected $table = 'clients';
    protected $guard_name = 'api';
    protected $fillable = [
        'name',
        'type',
        'contact_person',
        'phone',
        'email',
        'address_line1',
        'address_line2',
        'area',
        'city',
        'state',
        'pincode',
        'country',
        'website',
        'safety_ratings',
        'operating_reginons',
        'certifications',
        'specialties',
        'terms_conditions_policis',
        'is_active',
    ];

    public function assets()
    {
        return $this->hasMany(Asset::class, 'client_id');
    }

    public function operatorAssignments()
    {
        return $this->hasMany(BookingInquiryOperatorAssignment::class, 'operator_id');
    }

    // Relationship for operating regions (comma IDs)
    public function operatingCities()
    {
        return $this->belongsToMany(
            AirportCities::class,
            null,
            'operating_reginons', // comma-separated field
            'id'
        );
    }
}
