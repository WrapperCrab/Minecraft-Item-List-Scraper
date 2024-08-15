<?php
/**
* Plugin name: Minecraft List by W
*/

add_shortcode('minecraft-list','show_minecraft_list');

//hooks 'n stuff
register_activation_hook(__FILE__,'create_item_tables');
register_deactivation_hook(__FILE__,'delete_item_tables');
add_action('wp_enqueue_scripts','minecraft_list_js_init');
function minecraft_list_js_init(){
    //load the scripts needed for the plugin
    wp_register_script('minecraft-list-js',"https://www.mowinpeople.com/wp-content/plugins/minecraft-list-by-W/minecraft-list.js",array('jquery'));
    wp_enqueue_script('minecraft-list-js');
    wp_localize_script('minecraft-list-js','ajax_object',array('ajaxurl' => admin_url('admin-ajax.php')));
}
//let ajax call functions
//add_action(wp_ajax_(func called in ajax), func to call here);
add_action('wp_ajax_generate_minecraft_list_table_html','generate_minecraft_list_table_html_ajax');
add_action('wp_ajax_nopriv_generate_minecraft_list_table_html','generate_minecraft_list_table_html_ajax');

function create_item_tables(){
    create_blocks_table();
    create_items_table();
}
function create_blocks_table(){
    global $wpdb;
    //create blocks table
    $table_name = $wpdb->prefix.'MinecraftBlocks';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE {$table_name} (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255),
        versionAdded varchar(255),
        versionRemoved varchar(255),
        PRIMARY KEY (id)
    ) {$charset_collate}";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    //add data from blocks.csv
    $blocksFile = fopen("https://www.mowinpeople.com/wp-content/plugins/minecraft-list-by-W/all_blocks.csv","r");
    while(!feof($blocksFile)){
        $blockData = fgetcsv($blocksFile);
        if ($blockData[0]==="Name"){
            #this is the header row
            continue;
        }
        global $wpdb;
        $table_name = $wpdb->prefix.'MinecraftBlocks';
        $wpdb->insert(
            $table_name,
            array(
                'name' => $blockData[0],
                'versionAdded' => $blockData[5],
                'versionRemoved' => $blockData[6],
                )
        );
    }
    fclose($blocksFile);
}
function create_items_table(){
    global $wpdb;
    //create blocks table
    $table_name = $wpdb->prefix.'MinecraftItems';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE {$table_name} (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255),
        versionAdded varchar(255),
        versionRemoved varchar(255),
        PRIMARY KEY (id)
    ) {$charset_collate}";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    //add data from items.csv
    $itemsFile = fopen("https://www.mowinpeople.com/wp-content/plugins/minecraft-list-by-W/all_items.csv","r");
    while(!feof($itemsFile)){
        $itemData = fgetcsv($itemsFile);
        if ($itemData[0]==="Name"){
            #this is the header row
            continue;
        }
        global $wpdb;
        $table_name = $wpdb->prefix.'MinecraftItems';
        $wpdb->insert(
            $table_name,
            array(
                'name' => $itemData[0],
                'versionAdded' => $itemData[5],
                'versionRemoved' => $itemData[6],
                )
        );
    }
    fclose($itemsFile);
}
function delete_item_tables(){
    global $wpdb;
    $table_name = $wpdb->prefix . 'MinecraftBlocks';
    $sql = "DROP TABLE IF EXISTS {$table_name}";
    $wpdb->query($sql);
    $table_name = $wpdb->prefix . "MinecraftItems";
    $sql = "DROP TABLE IF EXISTS {$table_name}";
    $wpdb->query($sql);
}

function get_item_names($includeBlocks=true,$includeItems=true){
    global $wpdb;
    $blockTableName = $wpdb->prefix . "MinecraftBlocks";
    $itemTableName = $wpdb->prefix . "MinecraftItems";

    $sql="";
    if ($includeBlocks && $includeItems){
        $sql = "SELECT name FROM {$blockTableName} UNION
        SELECT name FROM {$itemTableName}
        ORDER BY name";
    }elseif ($includeBlocks || $includeItems) {
        $tableName = $includeBlocks ? $blockTableName : $itemTableName;
        $sql = "SELECT name FROM {$tableName}
        ORDER BY name";
    }
    $result = $wpdb->get_results($sql);
    foreach ($result as $item){
        $names[] = $item->name;
        // $versionsAdded[] = $item->versionAdded;
    }
    return $names;
}

function show_minecraft_list(){
    ob_start();
    $names = get_item_names();
    create_minecraft_list_html($names);
    return ob_get_clean();
}
function create_minecraft_list_html($names){
    //!!!Style should really be in <head>
    ?>
    <style>
    table, th, td {
        border: 0px;
        font-size: 1.17em;
        text-align: center;
    }
    p {
        font-size: 1.17em;
        text-align: center;
    }
    </style>
    <p>Show:
        <input type="checkbox" id="itemType1" name="itemType1" value="Blocks" checked>
        <label for="itemType1">Blocks</label>
        <input type="checkbox" id="itemType2" name="itemType2" value="Items" checked>
        <label for="itemType2">Items</label>
    </p>

    <button id="list-selection-submit" style="margin:0 auto;display:block" font-size=1.5em>Update List</button>

    <table id="minecraft-list" style="width:70vw;">
        <?php echo get_minecraft_list_table_html($names)?>
    </table>
    <?php
}
function get_minecraft_list_table_html($names){
    $tableHtml = '
    <tr>
        <th>List</th>
    </tr>';
    foreach ($names as $name){
        $tableHtml = $tableHtml . '
    <tr>
        <td>' . $name . '</td>
    </tr>';
    }
    return $tableHtml;
}

function generate_minecraft_list_table_html_ajax(){
    $includeBlocks = string_to_bool($_POST['includeBlocks']);
    $includeItems = string_to_bool($_POST['includeItems']);
    $names = get_item_names($includeBlocks,$includeItems);
    echo get_minecraft_list_table_html($names);
    wp_die();
}

function string_to_bool($string){
    $lowerString = strtolower($string);
    if ($lowerString==="true"){
        return true;
    }elseif ($lowerString==="false"){
        return false;
    }
    return null;
}


?>
