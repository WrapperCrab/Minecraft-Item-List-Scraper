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
add_action('wp_ajax_get_names','get_names_ajax');
add_action('wp_ajax_nopriv_get_names','get_names_ajax');

add_option('list',[]);//stores the current list of minecraft items as an array of strings

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
                'versionAddedID' => $versionAddedID,
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
    $sql = "SELECT id FROM {$versionTableName} WHERE {$versionTableName}.name='{$trueVersionName}'";
    $result = $wpdb->get_row($sql);

    if (!is_null($result)){
        return $result->id;
    }
    return null;
}

function get_item_names($versionFilterType="all_items",$versionValue=999,$includeBlocks=true,$includeItems=true,$sortingValues=[["alphabetical",true,"ascending",1]],$includeSQL=false,$debugValues=[]){
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
    switch ($versionFilterType){
        case "all_items":
            $sql .= " SELECT name FROM neededItems ";
            break;
        case "exists_in_version":
            $sql .= ", " . get_added_items_query($versionValue,$versionTableName);
            $sql .= ", " . get_not_removed_items_query($versionValue,$versionTableName);
            $sql .= " SELECT name FROM (addedItems NATURAL JOIN notRemovedItems) ";
            break;
        case "added_in_version":
            $sql .= ", " . get_newly_added_items_query($versionValue, $versionTableName);
            $sql .= " SELECT name FROM addedItems ";
            break;
        case "removed_in_version":
            $sql .= ", " . get_newly_removed_items_query($versionValue, $versionTableName);
            $sql .= " SELECT name FROM removedItems ";
            break;
        default:
            //This should never happen
            return ["Unkown version filterType of " . $versionFilterType];
    }
    //order choices
    $ordering = "";
    for ($i=0; $i<count($sortingValues); $i++){
        $values = $sortingValues[$i];
        //add the comma for multiple orders
        if ($i===0){
            $ordering .= "ORDER BY ";
        }else{
            $ordering .= ", ";
        }
        //add the column name
        switch ($values[0]){
            case "alphabetical":
                $ordering .= "name";
                break;
            case "name_length":
                $ordering .= "CHAR_LENGTH(name)";
                break;
            case "age":
                $ordering .= "versionAddedID";
                break;
            default:
                return ["Unknown sorting type of " . $values[0]];
        }
        //add the order direction
        if ($values[2]==="ascending"){
            $ordering .= " ASC";
        }else if ($values[2]==="descending"){
            $ordering .= " DESC";
        }else{
            return ["Unkown sorting direction of " . $values[2]];
        }
    }
    if ($ordering!==""){
        $sql .= $ordering;
    }

    $sql .= ";";
    $result = $wpdb->get_results($sql);
    $names=[];
    foreach ($result as $item){
        $names[] = $item->name;
        // $versionsAdded[] = $item->versionAdded;
    }
    //debug stuff
    if ($includeSQL){
        $names[] = $sql;
    }
    foreach ($debugValues as $value){
        $names[] = $value;
    }
    return $names;
}

function get_needed_items_query($includeBlocks, $includeItems, $blockTableName, $itemTableName){
    $sql = " neededItems AS";
    if ($includeItems===true && $includeBlocks===true){
        $neededItems = " (SELECT name, versionAddedID, versionRemovedID FROM {$blockTableName} UNION
        SELECT name, versionAddedID, versionRemovedID FROM {$itemTableName})";
    }elseif ($includeItems===true) {
        $neededItems = " (SELECT name, versionAddedID, versionRemovedID FROM {$itemTableName})";
    }elseif ($includeBlocks===true) {
        $neededItems = " (SELECT name, versionAddedID, versionRemovedID FROM {$blockTableName})";
    }else{
        return null;
    }
    $sql .= $neededItems;
    return $sql;
}
function get_added_items_query($versionValue, $versionTableName){
    //get table of neededItems names added before or at $version
    $sql = "addedItems AS
        (SELECT neededItems.name, neededItems.versionAddedID FROM neededItems
        LEFT JOIN {$versionTableName}
        ON neededItems.versionAddedID={$versionTableName}.id
        WHERE ({$versionTableName}.value<={$versionValue}) OR (neededItems.versionAddedID IS NULL))";
    return $sql;
}
function get_newly_added_items_query($versionValue, $versionTableName){
    //get table of neededItems names added at $version
    $sql = "addedItems AS
        (SELECT neededItems.name, neededItems.versionAddedID FROM neededItems
        LEFT JOIN {$versionTableName}
        ON neededItems.versionAddedID={$versionTableName}.id
        WHERE ({$versionTableName}.value IS NOT NULL) AND ({$versionTableName}.value={$versionValue}))";
    return $sql;
}
function get_newly_removed_items_query($versionValue, $versionTableName){
    //get table of neededItems names removed in $version
    $sql = "removedItems AS
        (SELECT neededItems.name, neededItems.versionAddedID FROM neededItems
        LEFT JOIN {$versionTableName}
        ON neededItems.versionRemovedID={$versionTableName}.id
        WHERE ({$versionTableName}.value IS NOT NULL) AND ({$versionTableName}.value={$versionValue}))";
    return $sql;
}
function get_not_removed_items_query($versionValue, $versionTableName){
    //get table of neededItems names removed after $version
    $sql = "notRemovedItems AS
        (SELECT neededItems.name, neededItems.versionAddedID FROM neededItems
        LEFT JOIN {$versionTableName}
        ON neededItems.versionRemovedID={$versionTableName}.id
        WHERE ({$versionTableName}.value>{$versionValue}) OR (neededItems.versionRemovedID IS NULL))";
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
}
//html funcs
function show_minecraft_list(){
    ob_start();
    $names = get_item_names();
    update_option('list',$names);
    $versions = get_versions(false);
    create_minecraft_list_html($names,$versions);
    return ob_get_clean();
}
function create_minecraft_list_html($names, $versions, $numColumns=1){
    ?>
    <head>
        <style>
        table{
            table-layout: fixed;
            border-collapse: collapse;
            /* display: block; */
        }
        td {
            border: 1px solid black;
            width: 160px;
            min-width: 160px;
            font-size: 1vw;
            text-align: center;
        }
        h1, h2, h3 {
            text-align: center;
        }
        p {
            /* font-size: 1.17em; */
            text-align: center;
            /* margin-top: 0.1em;
            margin-bottom: 0.1em;
            margin-left: 0;
            margin-right: 0; */
            /* margin:0; */
        }
        select {
            /* display: block; */
            /* margin: 0 auto; */
            /* padding:0px; */
        }
        fieldset {
            padding-top: 1em;
            padding-bottom: 1em;
            margin-top: 0em;
            margin-bottom: 0em;
            text-align:center;
        }
        fieldset legend{
            padding-top: 0em;
            padding-bottom: 0em;
            margin-top: 0em;
            margin-bottom: 0em;
        }
        fieldset p{
            padding-top: 0em;
            padding-bottom: 0em;
            margin-top: 0em;
            margin-bottom: 0em;
        }
        .radio-label .checkbox-label {
            text-align: center;
            vertical-align: top;
            margin-right: 5em;
            /* margin-right: 3%; */
            /* padding:0px; */
        }
        .radio-input .checkbox-input {
            text-align: center;
            vertical-align: top;
            margin-left: 5em;
            /* padding:0px; */
        }
        .table-container{
            overflow-x: scroll;
        }
        .center-container{
            /* display:block; */
            display:flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            margin-top:0px;
            margin-bottom:0px;
            padding-top:0px;
            padding-bottom:0px;
        }
        .right-container{
            display:flex;
            justify-content: flex-end;
            margin-top:0.5em;
            margin-bottom:0.5em;
            padding-top:0px;
            padding-bottom:0px;
        }
        *:disabled{
            opacity: 0.2;
        }
        </style>
    </head>
    <body>
        <h3>Options</h3>
        <fieldset>
            <legend>Version Filter Options</legend>
            <div class="center-container">
                <div>
                    <input type="radio" class="radio-input" id="all_items" value="all_items" name="version_filter" checked>
                    <label for="all_items" class="radio-label">All Items</label>
                </div>
                <div>
                    <input type="radio" class="radio-input" id="exists_in_version" value="exists_in_version" name="version_filter">
                    <label for="exists_in_version" class="radio-label">Exists in Version</label>
                </div>
                <div>
                    <input type="radio" class="radio-input" id="added_in_version" value="added_in_version" name="version_filter">
                    <label for="added_in_version" class="radio-label">Added in Version</label>
                </div>
                <div>
                    <input type="radio" class="radio-input" id="removed_in_version" value="removed_in_version" name="version_filter">
                    <label for="removed_in_version" class="radio-label">Removed in Version</label>
                </div>
            </div>
            <br>
            <p>Version:
                <select name="minecraft_version" id="minecraft_version">
                    <?php
                    foreach ($versions as $version){?>
                        <option value=<?php echo $version->value?>><?php echo $version->name?></option>
                    <?php
                    }
                    ?>
                </select>
            </p>
        </fieldset>
        <fieldset>
            <legend>Item Type Filter Options</legend>
            <div class="center-container">
                <div>
                    <input type="checkbox" class="checkbox-input" id="item_type_1" name="itemType1" value="Blocks" checked>
                    <label for="itemType1" class="checkbox-label">Blocks</label>
                </div>
                <div>
                    <input type="checkbox" class="checkbox-input" id="item_type_2" name="itemType2" value="Items" checked>
                    <label for="itemType2" class="checkbox-label">Items</label>
                </div>
            </div>
        </fieldset>
        <fieldset>
            <legend>Sorting Options</legend>
            <div class="center-container">
                <div style="min-width:175px; max-width:175px; text-align:left;">
                    <input type="checkbox" class="checkbox-input" id="alphabetical_sort" value="alphabetical_sort" checked>
                    <label for="alphabetical_sort" class="checkbox-label">Alphabetical</label>
                </div>
                <div>
                    <input type="radio" class="radio-input" id="alphabetical_sort_ascending" name="alphabetical_sort_direction" value="ascending" checked>
                    <label for="alphabetical_sort_ascending" class="radio-label">Ascending</label>
                </div>
                <div>
                    <input type="radio" class="radio-input" id="alphabetical_sort_descending" name="alphabetical_sort_direction" value="descending">
                    <label for="alphabetical_sort_descending" class="radio-label">Descending</label>
                </div>
                <select name="priority" id="alphabetical_sort_priority">
                    <option>1</option>
                    <option>2</option>
                    <option>3</option>
                </select>
            </div>
            <br>

            <div class="center-container">
                <div style="min-width:175px; max-width:175px; text-align:left;">
                    <input type="checkbox" class="checkbox-input" id="name_length_sort" value="name_length_sort">
                    <label for="name_length_sort" class="checkbox-label">Name Length</label>
                </div>
                <div>
                    <input type="radio" class="radio-input" id="name_length_sort_ascending" name="name_length_sort_direction" value="ascending" checked>
                    <label for="name_length_sort_ascending" class="radio-label">Ascending</label>
                </div>
                <div>
                    <input type="radio" class="radio-input" id="name_length_sort_descending" name="name_length_sort_direction" value="descending">
                    <label for="name_length_sort_descending" class="radio-label">Descending</label>
                </div>
                <select name="priority" id="name_length_sort_priority">
                    <option>1</option>
                    <option>2</option>
                    <option>3</option>
                </select>
            </div>
            <br>

            <div class="center-container">
                <div style="min-width:175px; max-width:175px; text-align:left;">
                    <input type="checkbox" class="checkbox-input" id="age_sort" value="age_sort">
                    <label for="age_sort" class="checkbox-label">Age</label>
                </div>
                <div>
                    <input type="radio" class="radio-input" id="age_sort_ascending" name="age_sort_direction" value="ascending" checked>
                    <label for="age_sort_ascending" class="radio-label">Ascending</label>
                </div>
                <div>
                    <input type="radio" class="radio-input" id="age_sort_descending" name="age_sort_direction" value="descending">
                    <label for="age_sort_descending" class="radio-label">Descending</label>
                </div>
                <select name="priority" id="age_sort_priority">
                    <option>1</option>
                    <option>2</option>
                    <option>3</option>
                </select>
            </div>
        </fieldset>
        <fieldset>
            <legend>Display Options</legend>
            <label for="num_columns" class="number-label">Number of Columns: </label>
            <input type="text" inputmode="numeric" pattern="[0-9]*" class="number-input" id="num_columns" value="1" style="max-width:50px;">
        </fieldset>
        <button id="list_selection_submit" style="margin-top:1em;margin-bottom:0.5em;display:block;font-size:2em;">Update List</button>

        <h2>List</h2>
        <div class="right-container">
            <button id="copy_to_clipboard" style="font-size:1em;">Copy List to Clipboard</button>
        </div>
        <div class="right-container">
            <button id="export_to_csv" style="font-size:1em;">Export List to CSV</button>
        </div>
        <div id="minecraft_list_container" class="table-container">
            <table id="minecraft_list" cellpadding="5">
                <?php echo get_minecraft_list_table_html($names,$numColumns)?>
            </table>
        </div>
    </body>
    <?php
}
function get_minecraft_list_table_html($names,$numColumns){
    $tableHtml = "";
    $keepgoing = true;
    $nameIndex = 0;
    $numNames = count($names);
    $name = $names[$nameIndex];
    while ($keepgoing){
        $tableHtml .= '<tr>';
        for ($i=0;$i<$numColumns;$i++){
            $tableHtml .= '<td>' . $name . '</td>';
            //go to next name
            $nameIndex++;
            if ($nameIndex>=$numNames){
                $keepgoing = false;
                break;
            }
            $name = $names[$nameIndex];
        }
        $tableHtml .= '</tr>';
    }
    return $tableHtml;
}
//ajax funcs
function generate_minecraft_list_table_html_ajax(){
    $includeBlocks = string_to_bool($_POST['includeBlocks']);
    $includeItems = string_to_bool($_POST['includeItems']);
    $versionValue = (int)$_POST['versionValue'];
    $versionFilterType= $_POST['versionFilterType'];

    $alphabeticalSortValues = ['alphabetical',string_to_bool($_POST['sortAlphabetical']),$_POST['alphabeticalDirection'],(int)$_POST['alphabeticalPriority']];
    $nameLengthSortValues = ['name_length',string_to_bool($_POST['sortNameLength']),$_POST['nameLengthDirection'],(int)$_POST['nameLengthPriority']];
    $ageSortValues = ['age',string_to_bool($_POST['sortAge']),$_POST['ageDirection'],(int)$_POST['agePriority']];
    $sortingValues = get_filtered_sorting_values([$alphabeticalSortValues,$nameLengthSortValues,$ageSortValues]);

    $numColumns = (int)$_POST['numColumns'];

    $names = get_item_names($versionFilterType,$versionValue,$includeBlocks,$includeItems,$sortingValues);
    update_option('list',$names);
    echo get_minecraft_list_table_html($names,$numColumns);
    wp_die();
}
function get_names_ajax(){
    echo json_encode(get_option('list'));
    wp_die();
}

function get_filtered_sorting_values($sortData){
    //$sortData like: [[$thisSortName, $useThisSortBool, $thisSortDirection, $thisSortPriority],...]
    //converts $sortData into an array with only the selected sorts (useThisSortBool is true) in order of increasing priority

    //remove unselected sort types
    $filteredSortData = [];
    foreach ($sortData as $sortTypeData){
        if ($sortTypeData[1]===true){
            $filteredSortData[] = $sortTypeData;
        }
    }

    //Order the sort types by priority
    $priorities = [];
    foreach ($filteredSortData as $sortingOptions){
        $priorities[] = $sortingOptions[3];
    }
    array_multisort($priorities, SORT_ASC, $filteredSortData);

    return $filteredSortData;
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
