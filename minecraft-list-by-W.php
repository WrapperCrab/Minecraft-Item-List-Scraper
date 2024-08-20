<?php
/**
* Plugin name: Minecraft List by W
*/

add_shortcode('minecraft-list','show_minecraft_list');

//hooks 'n stuff
register_activation_hook(__FILE__,'create_tables');
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
//database write funcs
function create_tables(){
    create_versions_table();//Must be first
    create_blocks_table();
    create_items_table();
}
function create_versions_table(){
    global $wpdb;
    //create versions table
    $table_name = $wpdb->prefix.'MinecraftVersions';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE {$table_name} (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255),
        value smallint(9),
        PRIMARY KEY (id)
    ) {$charset_collate}";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    //add data from versions.csv
    $versionsFile = fopen("https://www.mowinpeople.com/wp-content/plugins/minecraft-list-by-W/all_versions.csv","r");
    while(!feof($versionsFile)){
        $versionData = fgetcsv($versionsFile);
        if ($versionData[0]==="version name"){
            #this is the header row
            continue;
        }
        global $wpdb;
        $wpdb->insert(
            $table_name,
            array(
                'name' => $versionData[0],
                'value' => $versionData[1],
                )
        );
    }
    fclose($versionsFile);
}
function create_blocks_table(){
    global $wpdb;
    //create blocks table
    $table_name = $wpdb->prefix.'MinecraftBlocks';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE {$table_name} (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255),
        versionAddedID mediumint(9),
        versionRemovedID mediumint(9),
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
        // find the version IDs
        $versionAddedID = get_version_id($blockData[5]);
        $versionRemovedID = get_version_id($blockData[6]);
        //insert this block
        global $wpdb;
        $wpdb->insert(
            $table_name,
            array(
                'name' => $blockData[0],
                'versionAddedID' => $versionAddedID,//!!!If this is null is it set as null in the database?
                'versionRemovedID' => $versionRemovedID,
                )
        );
    }
    fclose($blocksFile);
}
function create_items_table(){
    global $wpdb;
    //create items table
    $table_name = $wpdb->prefix.'MinecraftItems';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE {$table_name} (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255),
        versionAddedID mediumint(9),
        versionRemovedID mediumint(9),
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
        // find the version IDs
        $versionAddedID = get_version_id($itemData[5]);
        $versionRemovedID = get_version_id($itemData[6]);
        //insert this item
        global $wpdb;
        $wpdb->insert(
            $table_name,
            array(
                'name' => $itemData[0],
                'versionAddedID' => $versionAddedID,
                'versionRemovedID' => $versionRemovedID,
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
    $table_name = $wpdb->prefix . "MinecraftVersions";
    $sql = "DROP TABLE IF EXISTS {$table_name}";
    $wpdb->query($sql);
}
//query funcs
function get_version_id($versionName){
    $trueVersionName = $versionName;
    //special cases
    if ($versionName==="Java Edition pre-Classic Cave game tech test" || $versionName==="Java Edition pre-Classic rd-131655"){
        $trueVersionName = "Cave game tech test";
    }
    //find the version id
    global $wpdb;
    $versionTableName = $wpdb->prefix . "MinecraftVersions";
    $sql = "SELECT id FROM {$versionTableName} WHERE {$versionTableName}.name='{$trueVersionName}'";//!!!Does this work?
    $result = $wpdb->get_row($sql);

    if (!is_null($result)){
        return $result->id;
    }
    return null;
}
function get_item_names($includeBlocks=true,$includeItems=true,$versionValue=-1){//!!!Work in progress
    global $wpdb;
    $blockTableName = $wpdb->prefix . "MinecraftBlocks";
    $itemTableName = $wpdb->prefix . "MinecraftItems";
    $versionTableName = $wpdb->prefix . "MinecraftVersions";

    //alias definitions
    $sql = "WITH ";
    $neededItemsQuery = get_needed_items_query($includeBlocks,$includeItems,$blockTableName,$itemTableName);//creates table with alias neededItems
    if (is_null($neededItemsQuery)){
        return [];
    }
    $sql .= $neededItemsQuery;
    if ($versionValue!==-1){
        $sql .= ", " . get_added_items_query($versionValue,$versionTableName);
        $sql .=", " . get_not_removed_items_query($versionValue,$versionTableName);
        //actually query part
        $sql .= " SELECT name FROM (addedItems NATURAL JOIN notRemovedItems) ";
    }else{
        $sql .= " SELECT name FROM neededItems ";
    }
    $sql .= "ORDER BY name";
    $sql .= ";";

    $result = $wpdb->get_results($sql);
    $names=[];
    foreach ($result as $item){
        $names[] = $item->name;
        // $versionsAdded[] = $item->versionAdded;
    }
    return $names;
}

function get_needed_items_query($includeBlocks,$includeItems, $blockTableName, $itemTableName){
    $sql = " neededItems AS";
    if ($includeItems===true && $includeBlocks===true){
        $neededItems = " (SELECT * FROM {$blockTableName} UNION SELECT * FROM {$itemTableName})";
    }elseif ($includeItems===true) {
        $neededItems = " (SELECT * FROM {$itemTableName})";
    }elseif ($includeBlocks===true) {
        $neededItems = " (SELECT * FROM {$blockTableName})";
    }else{
        return null;
    }
    $sql .= $neededItems;
    return $sql;
}
function get_added_items_query($versionValue, $versionTableName){//!!!does not consider $version==-1
    //get table of neededItems names added before or at $version
    $sql = "addedItems AS
        (SELECT neededItems.name FROM neededItems
        LEFT JOIN {$versionTableName}
        ON neededItems.versionAddedID={$versionTableName}.id
        WHERE ({$versionTableName}.value<=$versionValue) OR (neededItems.versionAddedID IS NULL))";
    return $sql;
}
function get_not_removed_items_query($versionValue, $versionTableName){//!!!does not consider $version==-1
    //get table of neededItems names removed after $version
    $sql = "notRemovedItems AS
        (SELECT neededItems.name FROM neededItems
        LEFT JOIN {$versionTableName}
        ON neededItems.versionRemovedID={$versionTableName}.id
        WHERE ({$versionTableName}.value>$versionValue) OR (neededItems.versionRemovedID IS NULL))";
    return $sql;
}


function get_versions($ascending){
    global $wpdb;
    $versionTableName = $wpdb->prefix . "MinecraftVersions";
    $sql="SELECT * FROM {$versionTableName} ORDER BY value";
    if ($ascending){
        $sql = $sql . " ASC";
    }else{
        $sql = $sql . " DESC";
    }
    $result = $wpdb->get_results($sql);
    return $result;

    // foreach ($result as $version){
    //     $versionValues[] = $version->value;
    //     $versionNames[] = $version->name;
    // }
    // return [$versionValues,$versionNames];
}
//html funcs
function show_minecraft_list(){
    ob_start();
    $names = get_item_names();
    $versions = get_versions(false);
    create_minecraft_list_html($names,$versions);
    return ob_get_clean();
}
function create_minecraft_list_html($names, $versions){
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
    select {
        /* display: block; */
        margin: 0 auto;
    }
    </style>
    <p>Version:
        <select name="minecraft-version" id="minecraft-version">
            <?php
            foreach ($versions as $version){?>
                <option value=<?php echo $version->value?>><?php echo $version->name?></option>
            <?php
            }
            ?>
        </select>
    </p>

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
//ajax funcs
function generate_minecraft_list_table_html_ajax(){
    $includeBlocks = string_to_bool($_POST['includeBlocks']);//I think this is returning false when should be true
    $includeItems = string_to_bool($_POST['includeItems']);
    $versionValue = $_POST['versionValue'];
    $names = get_item_names($includeBlocks,$includeItems,$versionValue);
    echo get_minecraft_list_table_html($names);
    wp_die();
}
//helper funcs
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
