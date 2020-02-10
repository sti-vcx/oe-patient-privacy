<?php


namespace PatientPrivacy;


use Mi2\DataTable\RowAttributeFilterIF;

class ProviderRowAttributeFilter implements RowAttributeFilterIF
{
    public function calculateRowClass($row)
    {
        // TODO: Implement calculateRowClass() method.
        return "";
    }

    public function calculateRowId($row)
    {
        // The row id is the patient id
        return $row['id'];
    }
}
