var copyText = "";

jQuery(document).ready(function($){
    update_list();

    update_version_dropdown();
    update_sorting_options("alphabetical");
    update_sorting_options("name_length");
    update_sorting_options("age");

    jQuery("#list_selection_submit").click(function($){
        update_list();
    });
    jQuery("input[type='radio'][name=version_filter]").click(function($){
        update_version_dropdown();
    });
    jQuery('#alphabetical_sort').click(function($){
        update_sorting_options("alphabetical");
    });
    jQuery('#name_length_sort').click(function($){
        update_sorting_options("name_length");
    });
    jQuery('#age_sort').click(function($){
        update_sorting_options("age");
    });
    jQuery('#num_columns').change(function($){
        update_num_columns();
    });
    jQuery('#copy_to_clipboard').click(function($){
        navigator.clipboard.writeText(copyText);
        alert("list copied to clipboard");
    });
});

function update_list(){
    //get data from input elements
    var versionValue = jQuery('#minecraft_version').find(":selected").val();
    var versionFilterType = jQuery("input[type='radio'][name=version_filter]:checked").val();
    var showBlocks = jQuery('#item_type_1').is(":checked");
    var showItems = jQuery('#item_type_2').is(":checked");

    var sortAlphabetical = jQuery('#alphabetical_sort').is(":checked");
    var alphabeticalDirection = jQuery("input[type='radio'][name=alphabetical_sort_direction]:checked").val();
    var alphabeticalPriority = jQuery('#alphabetical_sort_priority').find(":selected").val();

    var sortNameLength = jQuery('#name_length_sort').is(":checked");
    var nameLengthDirection = jQuery("input[type='radio'][name=name_length_sort_direction]:checked").val();
    var nameLengthPriority = jQuery('#name_length_sort_priority').find(":selected").val();

    var sortAge = jQuery('#age_sort').is(":checked");
    var ageDirection = jQuery("input[type='radio'][name=age_sort_direction]:checked").val();
    var agePriority = jQuery('#age_sort_priority').find(":selected").val();

    var numColumns = jQuery('#num_columns').val();

    //dump the old List and create the new list
    jQuery.ajax({
        type:"POST",
        url: ajax_object.ajaxurl,
        data:{
            action: 'generate_minecraft_list_table_html',
            'includeBlocks':showBlocks,
            'includeItems':showItems,

            'versionValue':versionValue,
            'versionFilterType':versionFilterType,

            'sortAlphabetical':sortAlphabetical,
            'alphabeticalDirection':alphabeticalDirection,
            'alphabeticalPriority':alphabeticalPriority,

            'sortNameLength':sortNameLength,
            'nameLengthDirection':nameLengthDirection,
            'nameLengthPriority':nameLengthPriority,

            'sortAge':sortAge,
            'ageDirection':ageDirection,
            'agePriority':agePriority,

            'numColumns':numColumns,
        },
        success:function(response){
            jQuery("#minecraft_list").html(response);
            copyText = generate_list_copy_text();
        },
        error:function(errorObject, exception){
            console.log(exception);
        }
    });
}
function update_version_dropdown(){
    var versionFilterType = jQuery("input[type='radio'][name=version_filter]:checked").val();
    var disableDropdown = (versionFilterType=="all_items");
    jQuery("#minecraft_version").prop('disabled',disableDropdown);
}
function update_sorting_options(sortType){
    switch(sortType){
        case "alphabetical":
            var sortEnabled = jQuery('#alphabetical_sort').is(":checked");
            jQuery('#alphabetical_sort_ascending').prop('disabled',!sortEnabled);
            jQuery('#alphabetical_sort_descending').prop('disabled',!sortEnabled);
            jQuery('#alphabetical_sort_priority').prop('disabled',!sortEnabled);
            break;
        case "name_length":
            var sortEnabled = jQuery('#name_length_sort').is(":checked");
            jQuery('#name_length_sort_ascending').prop('disabled',!sortEnabled);
            jQuery('#name_length_sort_descending').prop('disabled',!sortEnabled);
            jQuery('#name_length_sort_priority').prop('disabled',!sortEnabled);
            break;
        case "age":
            var sortEnabled = jQuery('#age_sort').is(":checked");
            jQuery('#age_sort_ascending').prop('disabled',!sortEnabled);
            jQuery('#age_sort_descending').prop('disabled',!sortEnabled);
            jQuery('#age_sort_priority').prop('disabled',!sortEnabled);
            break;
        default:
            console.log("Unknown sorting option: ".concat(sortType));
    }
}
function update_num_columns(){
    var numColumns = jQuery('#num_columns').val();
    if ((!/^[0-9]*$/.test(numColumns)) || (/^0*$/.test(numColumns))) {
        //this is invalid input check if it can be rounded to nearest integer
        var betterValue = parseFloat(numColumns).toFixed(0);
        if (isNaN(betterValue) || betterValue==0){
            jQuery('#num_columns').val("1");
        }else{
            jQuery('#num_columns').val(betterValue.toString());
        }
    }
}

function generate_list_copy_text(){
    //get the array of names from php
    var names = new Array();
    var copyText = "";
    jQuery.ajax({
        type:"POST",
        url: ajax_object.ajaxurl,
        data:{
            action: 'get_names',
        },
        async: false,
        success:function(response){
            names = JSON.parse(response);
        }
    });
    for (var i=0;i<names.length;i++){
        var name = names[i];
        copyText += name + "\n";
    }
    return copyText;
}
