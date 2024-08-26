jQuery(document).ready(function($){
    update_list();
    update_version_dropdown();
    jQuery("#list_selection_submit").click(function($){
        update_list();
    });
    jQuery("input[type='radio'][name=version_filter]").click(function($){
        update_version_dropdown();
    })
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
            'alphabeticalDirection':alphabeticalDirection,//!!should really be a bool
            'alphabeticalPriority':alphabeticalPriority,

            'sortNameLength':sortNameLength,
            'nameLengthDirection':nameLengthDirection,//!!should really be a bool
            'nameLengthPriority':nameLengthPriority,

            'sortAge':sortAge,
            'ageDirection':ageDirection,//!!should really be a bool
            'agePriority':agePriority,
        },
        success:function(response){
            jQuery("#minecraft_list").html(response);
        },
        error:function(errorObject, exception){
            console.log(exception);
        }
    });
}
function update_version_dropdown(){
    var versionFilterType = jQuery("input[type='radio'][name=version_filter]:checked").val();
    if (versionFilterType=="all_items"){
        //disable the version dropdown
        jQuery("#minecraft_version").prop('disabled',true);
        jQuery("#minecraft_version").css('opacity','0.2');
    }else{
        //enable the version dropdown
        jQuery("#minecraft_version").prop('disabled',false);
        jQuery("#minecraft_version").css('opacity','1.0');
    }
}
