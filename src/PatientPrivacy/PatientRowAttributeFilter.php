<?php


namespace PatientPrivacy;


use Sti\DataTable\RowAttributeFilterIF;

class PatientRowAttributeFilter implements RowAttributeFilterIF
{
    public function calculateRowClass($row)
    {
        // TODO: Implement calculateRowClass() method.
        return "";
    }

    public function calculateRowId($row)
    {
        // The row id is the patient id
        return $row['pid'];
    }
}
