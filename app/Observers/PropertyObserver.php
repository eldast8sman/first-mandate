<?php

namespace App\Observers;

use App\Models\DueDate;
use App\Models\Property;
use App\Models\PropertyManager;
use App\Models\PropertySetting;
use App\Models\PropertyTenant;
use App\Models\PropertyUnit;

class PropertyObserver
{
    /**
     * Handle the Property "created" event.
     */
    public function created(Property $property): void
    {
        //
    }

    /**
     * Handle the Property "updated" event.
     */
    public function updated(Property $property): void
    {
        //
    }

    /**
     * Handle the Property "deleted" event.
     */
    public function deleted(Property $property): void
    {
        PropertySetting::where('property_id', $property->id)->delete();
        PropertyManager::where('property_id', $property->id)->delete();
        PropertyUnit::where('property_id', $property->id)->delete();
        PropertyTenant::where('property_id', $property->id)->delete();
        DueDate::where('property_id', $property->id)->delete();
    }

    /**
     * Handle the Property "restored" event.
     */
    public function restored(Property $property): void
    {
        //
    }

    /**
     * Handle the Property "force deleted" event.
     */
    public function forceDeleted(Property $property): void
    {
        //
    }
}
