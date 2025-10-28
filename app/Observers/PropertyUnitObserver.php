<?php

namespace App\Observers;

use App\Models\DueDate;
use App\Models\PropertyTenant;
use App\Models\PropertyUnit;

class PropertyUnitObserver
{
    /**
     * Handle the PropertyUnit "created" event.
     */
    public function created(PropertyUnit $propertyUnit): void
    {
        //
    }

    /**
     * Handle the PropertyUnit "updated" event.
     */
    public function updated(PropertyUnit $propertyUnit): void
    {
        //
    }

    /**
     * Handle the PropertyUnit "deleted" event.
     */
    public function deleted(PropertyUnit $propertyUnit): void
    {
        // Delete all property tenants associated with this unit
        PropertyTenant::where('property_unit_id', $propertyUnit->id)->delete();
        
        // Delete all due dates associated with this unit
        DueDate::where('property_unit_id', $propertyUnit->id)->delete();
    }

    /**
     * Handle the PropertyUnit "restored" event.
     */
    public function restored(PropertyUnit $propertyUnit): void
    {
        //
    }

    /**
     * Handle the PropertyUnit "force deleted" event.
     */
    public function forceDeleted(PropertyUnit $propertyUnit): void
    {
        //
    }
}
