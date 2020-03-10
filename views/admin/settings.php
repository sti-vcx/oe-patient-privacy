<?php
require_once("{$GLOBALS['srcdir']}/options.inc.php");

use OpenEMR\Common\Csrf\CsrfUtils;
use OpenEMR\Core\Header;
use OpenEMR\OeUI\OemrUI;

?>
<head>
    <?php Header::setupHeader(); ?>
    <title><?php echo $this->title ?></title>
    <link rel="stylesheet"
          href="<?php echo $GLOBALS['assets_static_relative']; ?>/datatables.net-dt/css/jquery.dataTables.css"
          type="text/css">
    <link rel="stylesheet"
          href="<?php echo $GLOBALS['assets_static_relative']; ?>/datatables.net-colreorder-dt/css/colReorder.dataTables.css"
          type="text/css">
    <script type="text/javascript"
            src="<?php echo $GLOBALS['assets_static_relative']; ?>/datatables.net/js/jquery.dataTables.js"></script>

    <style>
        #addressbook_list a:visited, a, a:visited {
            color: #337ab7;
        }

        p.tt-suggestion {
            width: 400px;
            border-color: grey;
            border-style: solid;
            border-width: 1px 2px 1px 2px;
            background-color:rgba(255, 255, 255, 0.9);
            margin: 0px;
            padding: 4px;
        }

        input[type="text"] {
            display: block;
            margin: 0px;
            width: 100%;
            height: 34px;
            padding: 6px 12px;
            font-size: 14px;
            line-height: 1.42857143;
            color: #555;
            background-color: #fff;
            background-image: none;
            border: 1px solid #ccc;
            border-radius: 4px;
            -webkit-box-shadow: inset 0 1px 1px rgba(0,0,0,.075);
            box-shadow: inset 0 1px 1px rgba(0,0,0,.075);
            -webkit-transition: border-color ease-in-out .15s,-webkit-box-shadow ease-in-out .15s;
            -o-transition: border-color ease-in-out .15s,box-shadow ease-in-out .15s;
            transition: border-color ease-in-out .15s,box-shadow ease-in-out .15s;
        }
    </style>

    <script src="assets/js/typeahead.bundle.min.js"></script>
</head>
<body class="body_top">


<div class="container">
    <div class="row">
        <div class="col-sm-12">
            <div class="page-header clearfix">
                <h2><?php echo $this->title; ?></h2>
            </div>
        </div>
    </div>
    <div class="row">
        <?php if ($this->message) { ?>
            <p class="bg-info"><?php echo $this->message; ?></p>
        <?php } ?>
    </div>

    <ul id="tabs" class="nav nav-tabs">
        <li role="presentation" class="active"><a id="patient-tab" data-toggle="tab" href="#patients">Patients</a></li>
        <li role="presentation"><a id="provider-tab" data-toggle="tab" href="#providers">Users</a></li>
        <li role="presentation"><a id="roles-tab" data-toggle="tab" href="#roles">Roles</a></li>
    </ul>

    <!-- Tab panes -->
    <div class="tab-content" style="padding-top: 20px;">
        <div role="tabpanel" class="tab-pane active" id="patients">

            <div class="row">
                <form class="form-inline" action="<?php echo $GLOBALS['webroot'] ?>/interface/perfecttranscription/index.php">
                    <div class="form-group">
                        <label for="provider-filter-select">Filter by Provider</label>
                        <select name="provider-filter-select" id="filter-by-provider" class="form-control">
                            <option value="">-- Any --</option>
                            <?php foreach ($this->providers as $provider) { ?>}
                            <option value="<?php echo $provider['id']; ?>"><?php echo $provider['name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <button id="attach-patient-to-provider-button" class="btn btn-default btn-add"><?php echo xlt('Attach Patient'); ?></button>
                    </div>
                </form>

            </div>
            <br>

                <table id="patient-data-table" class="display" style="padding: 10px; width:100%">
                    <thead>
                    <tr>
                        <th>Provider ID</th>
                        <th>Provider Name</th>
                        <th>Last Name</th>
                        <th>First Name</th>
                        <th>DOB</th>
                        <th>PID</th>
                    </tr>
                    </thead>
                    <tfoot>
                    <tr>
                        <th>Provider ID</th>
                        <th>Provider Name</th>
                        <th>Last Name</th>
                        <th>First Name</th>
                        <th>DOB</th>
                        <th>PID</th>
                    </tr>
                    </tfoot>
                </table>

        </div>
        <div role="tabpanel" class="tab-pane" id="providers">
            <div class="row">

                <br>

                <table id="provider-data-table" class="display" style="padding: 10px; width:100%">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Last Name</th>
                        <th>First Name</th>
                        <th>Username</th>
                        <th>Role</th>
                    </tr>
                    </thead>
                    <tfoot>
                    <tr>
                        <th>ID</th>
                        <th>Last Name</th>
                        <th>First Name</th>
                        <th>Username</th>
                        <th>Role</th>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div role="tabpanel" class="tab-pane" id="roles">
            <div class="row">

                <br>
                <form id="roles-form">
                    <div class="form-group">
                        <label for="roles">Roles Excluded From Patient Privacy (can see all patients)</label>
                        <select multiple name="roles[]" size="10" id="role-select" class="form-control">
                            <?php foreach ($this->roles as $role) { ?>
                            <option value="<?php echo $role->id; ?>" <?php echo ($role->excluded) ? "selected" : ""; ?>><?php echo $role->title; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-sm-12">
                            <button id="save-excluded-roles-button" class="btn btn-prinary"><?php echo xlt('Save'); ?></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Attach Provider To Patient -->
<div class="modal fade" id="attach-provider-to-patient" tabindex="-1" role="dialog" aria-labelledby="attach-provider-to-patient-label">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">Provider Access</h4>
            </div>
            <div class="modal-body">
                <form id="patient-privacy-form">

                    <input id="currently-active-pid" type="hidden" value="" name="pid">

                    <!-- Tabs within patient privacy modal -->
                    <ul id="provider-access-tabs" class="nav nav-tabs">
                        <li role="presentation" class="active"><a id="direct-access" data-toggle="tab" href="#direct-access-tab">Direct Access</a></li>
                        <li role="presentation"><a id="supervisor-access" data-toggle="tab" href="#supervisor-access-tab">Access via Supervisor</a></li>
                    </ul>

                    <div class="tab-content" style="padding-top: 20px;">

                        <!-- Provider Direct Access Tab Pane -->
                        <div role="tabpanel" class="tab-pane active" id="direct-access-tab">
                            <div class="form-group">
                                <label for="providers">Providers</label>
                                <select multiple name="providers[]" size="10" id="patient-provider-select" class="form-control">
                                <!-- This is left empty because it is dynamically populated when the modal loads. -->
                                </select>
                            </div>
                        </div>

                        <!-- Supervisor Access tab pane -->
                        <div role="tabpanel" class="tab-pane" id="supervisor-access-tab">

                            <!-- Table shows how a supervisor has access to this patient -->
                            <table class="table" id="supervisor-assignment-table">
                                <caption>Access Via Supervisor Assignment</caption>
                                <thead>
                                    <tr>
                                        <th>Supervisor</th>
                                        <th>Provider</th>
                                        <th>&nbsp;</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <!-- this is left empty, is populated dynamically by ajax call to set supervisors who have access -->
                                </tbody>
                            </table>

                            <div class="row">
                                <div class="col-sm-12">
                                    <button id="attach-supervisor-button" class="btn btn-default btn-add"><?php echo xlt('Attach Supervisor'); ?></button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </form>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="save-patient-privacy-form">Save changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Attach Provider To Supervisor -->
<div class="modal fade" id="attach-provider-to-supervisor" tabindex="-1" role="dialog" aria-labelledby="attach-provider-to-supervisor-label">
    <div class="modal-dialog" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">Supervisor Relationships for <span id="selected-provider"></span></h4>
            </div>
            <div class="modal-body">
                <form id="attach-provider-to-supervisor-form">
                    <div class="form-group">
                        <label for="supervisor">Supervisors</label>
                        <input type="hidden" name="provider_id" id="attach-provider-to-supervisor-provider-id">
                        <select id="supervisor-select-list" name="supervisors[]" size="10" multiple class="form-control">
                            <!-- This is empty because it is dynamically populated based on the selected provider -->
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button id="save-attach-provider-to-supervisor" type="button" class="btn btn-primary">Save changes</button>
            </div>
        </div>
    </div>
</div>

<!-- attach a patient to a provider -->
<div class="modal fade" id="attach-patient-to-provider-new" tabindex="-1" role="dialog" aria-labelledby="attach-patient-to-provider-label">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="attach-patient-to-provider-modal-label">Attach Patient to Provider</h4>
            </div>
            <div class="modal-body">
                <h3>Patient</h3>
                <hr>
                <form id="attach-patient-to-provider-form">
                    <input name="pid" id="patient-pid" type="hidden" value="">
                    <div class="form-group form-horizontal" id="patient-full-name-typeahead">
                        <label for="patient-fname" class="control-label">Full Name:</label>
                        <div id="patient-container">
                            <input required name="patient_name" type="text" style="display: block; width: 100%;" class="form-control typeahead" id="patient-full-name" placeholder="Last, First">
                            <span id="patient-last-encounter-date"></span>
                        </div>
                    </div>
                    <div class="form-group form-horizontal">
                        <label for="patient-dob" class="control-label">DOB:</label>
                        <input name="DOB" type="text" data-date-format='dd/mm/yyyy' class="form-control typeahead" id="patient-dob" placeholder="mm/dd/yyyy" readonly>
                    </div>
                    <div class="form-group form-horizontal">
                        <label for="patient-sex" class="control-label">Sex:</label>
                        <input name="sex" type="sex" class="form-control" id="patient-sex" readonly>
                    </div>


                    <h3>Attach to Provider</h3>
                    <hr>

                    <div class="form-group">
                        <label for="provider-filter-select">Provider</label>
                        <select name="provider_id" id="provider-to-attach" class="form-control">
                            <?php foreach ($this->providers as $provider) { ?>}
                                <option value="<?php echo $provider['id']; ?>"><?php echo $provider['name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button id="save-attach-patient-to-provider" type="button" class="btn btn-primary">Save changes</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {

        var last_clicked_pid = 0;
        var last_filtered_provider = 0;
        var last_clicked_provider_id = 0;

        var providers_with_access = [];
        var all_providers = [];

        var patient_table = $('#<?php echo $this->patientDataTable->getTableId(); ?>').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax" : {
               "url": "<?php echo $this->baseUrl(); ?>/index.php?action=admin!patient_data",
                "data": function(d) {
                   // Pass the provider_filter value as a request parameter for server-side filtering
                   d.provider_filter = $("#filter-by-provider").val();
                }
            },
            "columns": <?php echo $this->patientDataTable->getColumnJson(); ?>
        });

        // When we change the filter-by-provider select box, pass the filter to the datatable, and re-draw
        $("#filter-by-provider").change(function() {
            last_filtered_provider = $(this).val();
            patient_table.draw();
        });

        var provider_table = $('#<?php echo $this->providerDataTable->getTableId(); ?>').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax" : "<?php echo $this->baseUrl(); ?>/index.php?action=admin!provider_data",
            "columns": <?php echo $this->providerDataTable->getColumnJson(); ?>
        });

        $('#<?php echo $this->providerDataTable->getTableId(); ?> tbody').on( 'click', 'tr', function () {

            last_clicked_provider_id = $(this).attr('id');

            // Open the modal with this patient's providers
            $("#attach-provider-to-supervisor").modal();
        });

        $("#attach-provider-to-supervisor").on('show.bs.modal', function(event) {

            // clear the form
            $("#supervisor-select-list").text("");

            // set the hidden input on the form to be the id of the provider we clicked on in the table
            $("#attach-provider-to-supervisor-provider-id").val(last_clicked_provider_id);

            // when the provider-supervisor modal pops up, set the name of the provider
            $.ajax({
                method: "POST",
                url: "<?php echo $this->baseUrl(); ?>/index.php?action=admin!fetch_provider",
                data: { provider_id: last_clicked_provider_id }
            }).done(function (response) {
                var obj = JSON.parse(response);
                $("#selected-provider").text(obj.name);
            });

            // perform an AJAX request to get the providers that have access to this patient
            $.ajax({
                method: "POST",
                url: "<?php echo $this->baseUrl(); ?>/index.php?action=admin!fetch_supervisors_for_provider",
                data: { provider_id: last_clicked_provider_id }
            }).done(function (response) {

                var obj = JSON.parse(response);

                // For each provider that came back, add them to the multi-select
                $.each(obj, function (obj_key, obj_value) {

                    // when the provider-supervisor modal pops up,  populate multi-select
                    var option = $('<option>', {value: obj_value.id, text: obj_value.name});
                    if (obj_value.is_supervisor == 1) {
                        option.attr("selected", true);
                    }
                    $('#supervisor-select-list')
                        .append(option);
                });
            });
        });

        // Click save changes button on the Supervisor relationships modal
        $("#save-attach-provider-to-supervisor").on('click', function(e) {
            e.stopPropagation();
            e.preventDefault();

            var formData = $("#attach-provider-to-supervisor-form").serialize();

            $.post('<?php echo $this->baseUrl(); ?>/index.php?action=admin!attach_provider_to_supervisors',
                formData, function (response) {

                    alert("Successfully Saved");
                    // Successfully saved
                    $("#attach-provider-to-supervisor").modal('hide');

                    // redraw the table
                    provider_table.draw();
                });

        });

        // This triggers the new relationship modal
        $("#attach-patient-to-provider-button").on('click', function(e) {
            e.stopPropagation();
            e.preventDefault();

            // If there was a provider filter active, populate the provider select box with that provider
            $("#provider-to-attach").val(last_filtered_provider);

            // Display the modal
            $("#attach-patient-to-provider-new").modal();
        });

        $('#patient-data-table tbody').on( 'click', 'tr', function () {

            last_clicked_pid = $(this).attr('id');

            // Open the modal with this patient's providers
            $("#attach-provider-to-patient").modal();
        });

        // Dynamically created elements, need to parse whole document
        // If we have a supervisor-provider relationship set up, but we don't want to save it, click
        // the trash button to remove that row
        $(document).on('click', '.remove-pending-attach', function (e) {
            e.stopPropagation();
            e.preventDefault();
            $(this).closest('tr').remove().fast();
        });

        $(document).on('click', '.detach-supervisor', function(e) {
            e.stopPropagation();
            e.preventDefault();
            var tr = $(this).closest('tr');
            var provider_name = tr.find('.provider-obj').parent().text();
            var supervisor_name = tr.find('.supervisor-obj').parent().text();
            if (confirm("Are you sure you want to detach `"+supervisor_name+"` (supervisor) from `"+provider_name+"` (provider)?")) {

                var provider_id = tr.find('.provider-obj').val();
                var supervisor_id = tr.find('.supervisor-obj').val();
                var post_data = {
                    provider_id: provider_id,
                    supervisor_id: supervisor_id
                };

                $.post('<?php echo $this->baseUrl(); ?>/index.php?action=admin!detach_provider_from_supervisor',
                    post_data, function (response) {
                        // The provider-supervisor relationship was successfully detached, so remove the row
                        tr.remove().slow();
                    });
            }
        });
        // End dynamically created elements

        /**
         * This function resets the form on the modal, loading the most current relationships
         */
        function reset_patient_privacy_form() {

            // Reset the supervisor-provider table
            $("#supervisor-assignment-table tbody > tr").remove();

            // Clear out the previous options
            $('#patient-provider-select').text("");

            // perform AJAX request to get the supervisors who have access to this patient
            $.ajax({
                method: "POST",
                url: "<?php echo $this->baseUrl(); ?>/index.php?action=admin!fetch_supervisors_for_patient",
                data: {pid: last_clicked_pid}
            }).done(function (response) {
                var supervisor_obj = JSON.parse(response);
                // create the table of supervisors using this object
                $.each(supervisor_obj, function (key, supervisor_obj_value) {
                    var newRow = $("<tr>");
                    var cols = "";

                    cols += '<td><input type="hidden" class="supervisor-obj" name="supervisors[' + supervisor_obj_value.supervisor_id + '][supervisor_id]" value="' + supervisor_obj_value.supervisor_id + '">' + supervisor_obj_value.supervisor_name + '</td>';
                    cols += '<td><input type="hidden" class="provider-obj" name="supervisors[' + supervisor_obj_value.supervisor_id + '][provider_id]" value="' + supervisor_obj_value.provider_id + '">' + supervisor_obj_value.provider_name + '</td>';
                    cols += '<td><button class="btn btn-default detach-supervisor" name=""><i class="fa fa-trash" aria-hidden="true"></i></button></td>';

                    newRow.append(cols);
                    $("#supervisor-assignment-table").append(newRow);

                    // increment the global counter so we know how many
                    access_via_supervisor_row_cnt++;
                });

            });

            providers_with_access = [];
            all_providers = [];

            // perform an AJAX request to get the providers that have access to this patient
            $.ajax({
                method: "POST",
                url: "<?php echo $this->baseUrl(); ?>/index.php?action=admin!fetch_providers_for_patient",
                data: {pid: last_clicked_pid}
            }).done(function (response) {

                var obj = JSON.parse(response);

                // For each provider that came back, add them to the multi-select
                $.each(obj, function (obj_key, obj_value) {

                    // Store all providers
                    all_providers.push(obj_value);

                    var option = $('<option>', {value: obj_value.id, text: obj_value.name});
                    if (obj_value.has_access == 1) {
                        option.attr("selected", true);

                        // we can also add them to the has-access list
                        providers_with_access.push(obj_value);
                    }
                    $('#patient-provider-select')
                        .append(option);
                });
            });
        } // end function

        var access_via_supervisor_row_cnt = 10000; // start at 10K so we don't have a conflict with the actual provider IDs in the other rows
        $("#attach-supervisor-button").on("click", function (e) {

            e.stopPropagation();
            e.preventDefault();

            var newRow = $("<tr>");
            var cols = "";

            cols += '<td><select class="new-supervisor-access form-control" name="supervisors[' + access_via_supervisor_row_cnt + '][supervisor_id]"></select></td>';
            cols += '<td><select class="new-provider-access form-control" name="supervisors[' + access_via_supervisor_row_cnt + '][provider_id]"></select></td>';
            cols += '<td><button class="btn btn-default remove-pending-attach" name="select' + access_via_supervisor_row_cnt + '"><i class="fa fa-trash" aria-hidden="true"></i></button></td>';

            newRow.append(cols);
            $("#supervisor-assignment-table").append(newRow);

            // Set the options using the providers that have access to this patient
            $.each(providers_with_access, function(key, obj_value) {
                var option = $('<option>', { value : obj_value.id, text : obj_value.name });
                $('.new-provider-access')
                    .append(option);
            });

            // Set all providers as option to be supervisor
            $.each(all_providers, function(key, obj_value) {
                var option = $('<option>', { value : obj_value.id, text : obj_value.name });
                $('.new-supervisor-access')
                    .append(option);
            });
            access_via_supervisor_row_cnt++;
        });

        // When the Provider Access modal is shown, this function is called to set up the modal
        $('#attach-provider-to-patient').on('show.bs.modal', function (event) {

            // Set the pid for the form
            $('#currently-active-pid').val(last_clicked_pid);

            // perform an AJAX request to get the providers that have access to this patient
            $.ajax({
                method: "POST",
                url: "<?php echo $this->baseUrl(); ?>/index.php?action=admin!fetch_patient",
                data: { pid: last_clicked_pid }
            }).done(function(response) {
                var patient_obj = JSON.parse(response);
                $("#myModalLabel").text("Provider Access for "+patient_obj.name)
            });

            // Reset the form
            reset_patient_privacy_form();
        });

        $("#save-attach-patient-to-provider").on('click', function(e) {
            e.stopPropagation();
            e.preventDefault();
            var formData = $("#attach-patient-to-provider-form").serialize();
            $.post('<?php echo $this->baseUrl(); ?>/index.php?action=admin!attach_patient_to_provider',
                formData, function (response) {

                    alert("Successfully Saved");
                    // Successfully saved
                    $("#attach-patient-to-provider-new").modal('hide');

                    // redraw the table
                    patient_table.draw();
                });
        });

        // When we click the "Save Changes" button on the Provider access, modal, we have to parse our forms and
        // send data to the server
        $("#save-patient-privacy-form").on('click', function(e) {
            e.stopPropagation();
            e.preventDefault();
            var formData = $("#patient-privacy-form").serialize();
            $.post('<?php echo $this->baseUrl(); ?>/index.php?action=admin!attach_providers_to_supervisors',
                formData, function (response) {

                // We need to reload the provider/supervisor relationships in case any providers were removed.
                reset_patient_privacy_form();

                alert("Successfully Saved");
                // Successfully saved
                //$("#attach-provider-to-patient").modal('hide');
            });

        });


        // On roles tab, detect when the options change
        $("#save-excluded-roles-button").click(function(e) {
            e.stopPropagation();
            e.preventDefault();
            var formData = $("#roles-form").serialize();
            $.post('<?php echo $this->baseUrl(); ?>/index.php?action=admin!set_excluded_roles',
                formData, function (response) {
                    alert("Successfully Saved");
                });
        });
       // $("#patient-dob").mask("00/00/0000");
       //  $(".tt-suggestion").on('click', function() {
       //      $(".tt-menu").hide();
       //  });

        var patientSearch = new Bloodhound({
            datumTokenizer: Bloodhound.tokenizers.whitespace,
            queryTokenizer: Bloodhound.tokenizers.whitespace,
            remote: {
                url: '<?php echo $this->baseUrl(); ?>/index.php?action=admin!patient_search&query=%QUERY',
                wildcard: '%QUERY'
            }
        });

        $('#patient-full-name').typeahead({
            hint: true,
            highlight: true,
            minLength: 2
        }, {
            name: 'patient-search',
            source: patientSearch,
            display: "name",
            templates: {
                suggestion: function ( e ) {
                    return '<p>' + e.displayKey + '</p>';
                }
            },
            limit: 20
        });

        $('#patient-dob').typeahead({
            hint: true,
            highlight: true,
            minLength: 2
        }, {
            name: 'patient-search',
            source: patientSearch,
            display: "DOB",
            templates: {
                suggestion: function ( e ) {
                    return '<p>' + e.displayKey + '</p>';
                }
            },
            limit: 20
        });

        $('#patient-full-name, #patient-dob').bind('typeahead:selected', function(obj, datum, name) {
            //alert(JSON.stringify(obj)); // object
            // outputs, e.g., {"type":"typeahead:selected","timeStamp":1371822938628,"jQuery19105037956037711017":true,"isTrigger":true,"namespace":"","namespace_re":null,"target":{"jQuery19105037956037711017":46},"delegateTarget":{"jQuery19105037956037711017":46},"currentTarget":
            //alert(JSON.stringify(datum)); // contains datum value, tokens and custom fields
            // outputs, e.g., {"redirect_url":"http://localhost/test/topic/test_topic","image_url":"http://localhost/test/upload/images/t_FWnYhhqd.jpg","description":"A test description","value":"A test value","tokens":["A","test","value"]}
            // in this case I created custom fields called 'redirect_url', 'image_url', 'description'
//        alert(datum.DOB);
//        alert(datum.sex);
            $("#patient-full-name").val( datum.name );
            $("#patient-dob").val( datum.DOB );
            $("#patient-sex").val( datum.sex );
            $("#patient-pid").val( datum.pid );
            var lastEncHTML = 'No encounters';
            if ( datum.lastEncounter ) {
                lastEncHTML = "Last Encounter: "+datum.lastEncounter;
            }
            $("#patient-last-encounter-date").text( lastEncHTML );
            //var isEncToday = moment( datum.lastEncounter).isSame( moment(), 'day' );
            //if ( isEncToday ) {
            //    $("#patient-last-encounter-date").css( "color", "red" );
            //} else {
                $("#patient-last-encounter-date").css( "color", "black" );
            //}
            //alert(JSON.stringify(name)); // contains dataset name
            // outputs, e.g., "my_dataset"

        });
    });

</script>
</body>
