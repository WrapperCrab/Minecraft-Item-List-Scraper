jQuery(document).ready(function($){
    jQuery("#list-selection-submit").click(function($){
        update_list();
    });
});

function update_list(){
    //get data from input elements
    var showBlocks = document.getElementById('itemType1').checked;
    var showItems = document.getElementById('itemType2').checked;
    console.log("showBlocks:");
    console.log(showBlocks);
    //dump the old List and create the new list
    jQuery.ajax({
        type:"POST",
        url: ajax_object.ajaxurl,
        data:{
            action: 'generate_minecraft_list_table_html',
            'includeBlocks':showBlocks,
            'includeItems':showItems
        },
        success:function(response){
            jQuery("#minecraft-list").html(response);
        }
    });

}
