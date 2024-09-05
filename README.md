# Minecraft Item List Scraper and Plugin
## A List of Every Item Ever in the Game
I created this program and plugin to create a list of every single item in Minecraft.\
Check it out on my website at https://www.mowinpeople.com/minecraft-item-list/

This repo consists of:
- a python file used to scrape minecraft.wiki/
- a WordPress plugin which creates an interactive list on my website

### Using the List
It's pretty simple! Navigate to https://www.mowinpeople.com/minecraft-item-list/ to find the list.\
Then, choose your options. You can filter by item types, find all the items added in a specific version,\
and change the number of columns the outputted list will show up in.

The Sorting Options merit some explanation. You can sort by 
- "alphabetical"
- "Age" (chronologically when each item was first added)
- "Name Length" (number of characters in the name)

In ascending or descending order. However there is something called priority you can choose as well what's that for?\
Well, you can select more than one sorting option!

This means you can sort by say "name length" then sort alphabetically for any ties/items with the same names.\
To do this you set the priority field. Lower priority items come first, so keep that in mind when making your perfect list!

### Using the Plugin
To create a list with this plugin on your site, use the shortcode `[minecraft-list]`

When the plugin is activated, it creates new tables in the WordPress database to store the information held in a few csv files
it expects to find, though you'll need to change the location in minecraft-list-by-W.php 
- `all_items.csv`
  - col 0: Item Names
  - col 5: Version Added
  - col 6: Version Removed
- `all_blocks.csv`
  - col 0: Block Names
  - col 5: Version Added
  - col 6: Version Removed
- `all_versions.csv`
  - col 0: Version Names
  - col 1: Version Value (newer versions have higher value than older versions)

Many columns are skipped because in my csv, those columns store data I didn't end up using for the final list.

### Using the Python Web Scraper
Because this script works by scraping data from minecraft.wiki, there is no guarantee that it will work in the future.
Even a small change by the developers that website to any of the relevant pages is likely to break this script.
If it doesn't work, I promise you, it worked at one point.

But that's okay! It's constant unexpected changes that keep us programmers in business!

`main.py` creates csv's of data from minecraft.wiki. To create these csv's edit the `main()` function before running
- call `create_blocks_csv(blocksLink)` to create a csv with data on every block in the game
- call `create_items_csv(itemsLink)` to create a csv with data on every item in the game
- call `create_version_history_csv(firstVersionLink)` to create a csv with data on every version of Minecraft Java Edition

`create_blocks_csv` and `create_items_csv` work by navigating to the main page on the wiki which lists every single block
and item respectively.
Then, it goes through each one, navigating to the link provided to gather information which it expects
in a specfic part of the page in a specific format. It saves this data to a csv file.

`create_version_history` works by starting at the first page for a version, gathering data, 
then navigating to the "next version" page listed.
There is indeed a page which lists all of the versions, but this was an easier implementation.

Finally, you must note that the data in the outputted csv's is not fully accurate. Many items do not have their own page
meaning data could not be gathered for those. There are also differences in page setup between items that my script can't
account for. Overall, I spent a significant amount of time editting the output to fix data which the script could not find.
I'm sure there's lots of errors I missed, such is life.

Happy scraping!
