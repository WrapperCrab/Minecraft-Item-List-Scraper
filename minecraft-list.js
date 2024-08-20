jQuery(document).ready(function($){
    jQuery("#list-selection-submit").click(function($){
        update_list();
    });
});

function update_list(){
    //get data from input elements
    var versionValue = jQuery('#minecraft-version').find(":selected").val();
    var showBlocks = jQuery('#itemType1').is(":checked");
    var showItems = jQuery('#itemType2').is(":checked");
    //dump the old List and create the new list
    jQuery.ajax({
        type:"POST",
        url: ajax_object.ajaxurl,
        data:{
            action: 'generate_minecraft_list_table_html',
            'includeBlocks':showBlocks,
            'includeItems':showItems,
            'versionValue':versionValue,
        },
        success:function(response){
            jQuery("#minecraft-list").html(response);
        }
    });

}
