from bs4 import BeautifulSoup
import csv
import requests

def main():
	blocksLink = "https://minecraft.wiki/w/Block"
	itemsLink = "https://minecraft.wiki/w/Item"
	# create_blocks_csv(blocksLink, numAnalyze=10, numSkip=939)
	create_items_csv(itemsLink, numAnalyze=30)

blockValueDict = {#attribute title: index in blockInfo
	"Name": 0,
	"Image url": 1,
	"Page url": 2,

	"Is redirect": 3,
	"Redirect url": 4,
	"Version added": 5,
	"Version removed": 6,
	"Obtainable": 7,
	"Craftable": 8,

	"Rarity tier": 9,
	"Renewable": 10,
	"Stackable": 11,
	"Tool": 12,
	"Tools": 12,
	"Blast resistance": 13,
	"Hardness": 14,
	"Luminous": 15,
	"Transparent": 16,
	"Waterloggable": 17,
	"Flammable": 18,
	"Catches fire from lava": 19
}
itemValueDict = {#attribute title: index in blockInfo
	"Name": 0,
	"Image url": 1,
	"Page url": 2,

	"Is redirect": 3,
	"Redirect url": 4,
	"Version added": 5,
	"Version removed": 6,
	#!!!Need to test to make sure these strings are right
	"Rarity tier": 9,
	"Renewable": 10,
	"Stackable": 11,
	"Durability": 12,
	"Restores": 13,
	"Status effects": 14,
}
toolDict = {#link extension: tool name
	"/w/Pickaxe": "Pickaxe",
	"/w/Wooden_Pickaxe": "Wooden Pickaxe",
	"/w/Stone_Pickaxe": "Stone Pickaxe",
	"/w/Iron_Pickaxe": "Iron Pickaxe",
	"/w/Diamond_Pickaxe": "Diamond Pickaxe",
	"/w/Axe": "Axe",
	"/w/Sword": "Sword",
	"/w/Shears": "Shears",
	"/w/Shovel": "Shovel",
	"/w/Hoe": "Hoe",
	"/w/Brush": "Brush",
	"/w/Bucket": "Bucket",
}
javaEditions = ["Java Edition pre-Classic", "Java Edition Classic", "Java Edition Indev",
				"Java Edition Infdev", "Java Edition Alpha", "Java Edition Beta", "Java Edition"]

#create items csv funcs
def create_items_csv(link, numAnalyze=0, numSkip=0):
	#create the output csv
	with open("items.csv", 'w', newline='') as file:
		writer = csv.writer(file)
		#generate header line of csv
		field = []
		prevValue = -1
		for value in list(itemValueDict.values()):
			if prevValue==value:
				continue
			header = list(itemValueDict.keys())[list(itemValueDict.values()).index(value)]
			field.append(header)
			prevValue = value
		writer.writerow(field)
		#navigate the main page
		req = requests.get(link)
		contents = req.text
		soup = BeautifulSoup(contents, 'html.parser')
		#find the items lists
		createEntitiesItemsDiv = soup.find(id="Items_that_create_blocks.2C_fluids_or_entities").parent.find_next_sibling('div')
		useableItemsDiv = soup.find(id="Items_with_use_in_the_world").parent.find_next_sibling('div')
		indirectUseItemsDiv = soup.find(id="Items_with_indirect_use_in_the_world").parent.find_next_sibling('div')
		spawnEggsItemsDiv = soup.find(id="Spawn_eggs").parent.find_next_sibling('div')
		# educationItemsDiv = soup.find(id="Exclusive_to_Minecraft_Education").parent.find_next_sibling('div')
		# unimplementedItemsDiv = soup.find(id="Unimplemented_items").parent.find_next_sibling('div')
		removedItemsDiv = soup.find(id="Removed_items").parent.find_next_sibling('div')
		# jokeItemsDiv = soup.find(id="Joke_items").parent.find_next_sibling('div')
		#add the items from these areas
		divsToAnalyze = [createEntitiesItemsDiv, indirectUseItemsDiv, spawnEggsItemsDiv, removedItemsDiv]
		add_items_from_divs(divsToAnalyze, writer, numAnalyze, numSkip)

	print("items.csv successfully created!")
def add_items_from_divs(divs, writer, numAnalyze=0, numSkip=0):
	leftSkip = numSkip
	leftAnalyze = numAnalyze
	for divIndex in range(len(divs)):
		conditions = add_items_from_div(divs[divIndex], writer, leftAnalyze, leftSkip)
		leftAnalyze = conditions[0]
		leftSkip = conditions[1]
		if numAnalyze and leftAnalyze==0:
			break
def add_items_from_div(div, writer, numAnalyze=0, numSkip=0, findVersionRemoved=False):
	#returns remaining number to analyze and skip as [numAnalyze, numSkip]
	leftAnalyze = numAnalyze
	leftSkip = numSkip
	items = div.ul.find_all("li")  # entries
	for index in range(len(items)):
		# handle skip and exit conditions
		if leftSkip:#check if there were ever any to skip
			leftSkip-=1
			continue
		if numAnalyze:
			if leftAnalyze==0:
				return [0, 0]
		item = items[index]
		#check that this is not a BE only item
		if len(item.find_all('sup'))!=0:
			#there is a superscript, check if it is BE
			superText = item.sup.i.span.a.text
			if superText=="BE":
				print("Item was skipped because it is a Bedrock Edition Exclusive")
				continue
		#find data for this item
		imageUrl = item.find_all("a")[0]['href']
		itemLinkTag = item.find_all("a")[1]  # returns the a tag that contains the link to its page
		pageUrl = itemLinkTag['href']
		itemName = itemLinkTag.contents[0].text
		print(index, " ", itemName)  #debug

		# get data from item's page
		itemInfo = get_item_info("https://minecraft.wiki" + pageUrl, findVersionRemoved=findVersionRemoved)
		# add data to the csv
		csvLine = [itemName, imageUrl, pageUrl]
		csvLine.extend(itemInfo)
		writer.writerow(csvLine)
		if numAnalyze:
			leftAnalyze-=1
	return [leftAnalyze, leftSkip]
def get_item_info(link, parameterShift=3, findVersionRemoved=False):
	#returns [is_redirect, redirect_url, version_added, version_removed,
	#rarity_tier, renewable, stackable, durability, restores, status_effects]
	#initialize itemInfo (info box info appended later)
	itemInfo = [False, link, "?", "?"]
	#navigate the item's page
	req = requests.get(link, allow_redirects=True)
	contents = req.text
	soup = BeautifulSoup(contents, 'html.parser')
	#check if there is a redirect
	redirectInfo = get_redirect_info(soup, link)
	itemInfo[itemValueDict["Is redirect"]-parameterShift]=redirectInfo[0]
	itemInfo[itemValueDict["Redirect url"]-parameterShift]=redirectInfo[1]
	#get data from History table
	historyTable = soup.find('table', {"data-description": "History"})
	if historyTable:
		itemInfo[2] = get_version_added(historyTable)
		if findVersionRemoved:
			itemInfo[3] = get_version_removed(historyTable)
	#get data from info box
	infoBox = soup.find('table', "infobox-rows")
	if infoBox:
		infoBoxInfo = get_item_info_box_info(infoBox)
		itemInfo.extend(infoBoxInfo)
	#return the needed data as a list
	return itemInfo
def get_item_info_box_info(infoBox, parameterShift=7):#!!!Fill in parameter shift default
	# returns [rarity_tier, renewable, stackable, durability, restores, status_effects]
	info = ["?", "?", "?", "?", "?", "?"]
	infoBoxValueAreas = infoBox.find_all('tr')
	for valueArea in infoBoxValueAreas:
		valueAreaInfo = get_info_box_value_area_info(valueArea)
		valueTitle = valueAreaInfo[0]
		value = valueAreaInfo[1]
		if valueTitle in itemValueDict.keys():
			info[itemValueDict[valueTitle]-parameterShift] = value
		else:
			print("Unknown item value title of ", valueTitle)
	return info
#create blocks csv funcs
def create_blocks_csv(link, numAnalyze=0, numSkip=0):
	#create the output csv
	with open("blocks.csv", 'w', newline='') as file:
		writer = csv.writer(file)
		#generate header line of csv
		field = []
		prevValue = -1
		for value in list(blockValueDict.values()):
			if prevValue==value:
				continue
			header = list(blockValueDict.keys())[list(blockValueDict.values()).index(value)]
			field.append(header)
			prevValue = value
		writer.writerow(field)
		#navigate the main page
		req = requests.get(link)
		contents = req.text
		soup = BeautifulSoup(contents, 'html.parser')
		#find the block lists (commented ones are unanalyzed)
		blocksDiv = soup.find(id="List_of_blocks").parent.find_next_sibling('div')
		technicalBlocksDiv = soup.find(id="Technical_blocks").parent.find_next_sibling('div').find_next_sibling('div')
		# educationBlocksDiv = soup.find(id="Education_Edition_Exclusive").parent.next_sibling.next_sibling
		outrightRemovedBlocksDiv = soup.find(id="Removed_blocks").parent.find_next_sibling('div')
		# substitutionRemovedBlocksDiv = soup.find(id="Removed_through_substitution").parent.next_sibling.next_sibling
		# metadataVariantBlocksDiv = soup.find(id="Extreme_metadata_variants").parent.next_sibling.next_sibling
		# jokeBlocksDiv = soup.find(id="Joke_blocks").parent.next_sibling.next_sibling

		divsToAnalyze = [blocksDiv, technicalBlocksDiv, outrightRemovedBlocksDiv]
		add_blocks_from_divs(divsToAnalyze, writer, numAnalyze, numSkip)
	print("blocks.csv successfully created!")
def add_blocks_from_divs(divs, writer, numAnalyze=0, numSkip=0):
	leftSkip = numSkip
	leftAnalyze = numAnalyze
	for divIndex in range(len(divs)):
		conditions = add_blocks_from_div(divs[divIndex], writer, leftAnalyze, leftSkip)
		leftAnalyze = conditions[0]
		leftSkip = conditions[1]
		if numAnalyze and leftAnalyze==0:
			break
def add_blocks_from_div(blocksDiv, writer, numAnalyze=0, numSkip=0, findVersionRemoved=False):
	#returns remaining number to analyze and skip as [numAnalyze, numSkip]
	leftAnalyze = numAnalyze
	leftSkip = numSkip
	blocks = blocksDiv.ul.find_all("li")  # entries
	for blockIndex in range(len(blocks)):
		# handle skip and exit conditions
		if leftSkip:#check if there were ever any to skip
			leftSkip-=1
			continue
		if numAnalyze:
			if leftAnalyze==0:
				return [0, 0]
		block = blocks[blockIndex]
		#check that this is not a BE only block
		if len(block.find_all('sup'))!=0:
			#there is a superscript, check if it is BE
			superText = block.sup.i.span.a.text
			if superText=="BE":
				print("block was skipped because it is a Bedrock Edition Exclusive")
				continue
		#find data for this block
		imageUrl = block.find_all("a")[0]['href']
		blockLinkTag = block.find_all("a")[1]  # returns the a tag that contains the link to its page
		pageUrl = blockLinkTag['href']
		blockName = blockLinkTag.contents[0]
		print(blockIndex, " ", blockName)  #debug
		# get data from block's page
		blockInfo = get_block_info("https://minecraft.wiki" + pageUrl, findVersionRemoved=findVersionRemoved)
		# add data to the csv
		csvLine = [blockName, imageUrl, pageUrl]
		csvLine.extend(blockInfo)
		writer.writerow(csvLine)
		if numAnalyze:
			leftAnalyze-=1
	return [leftAnalyze, leftSkip]
def get_block_info(link, parameterShift=3, findVersionRemoved=False):
	#returns [is_redirect, redirect_url, version_added, version_removed,
	#obtainable, craftable, rarity_tier, renewable, stackable, tool, blast_resistance,
	#hardness, luminous, transparent, waterloggable, flammable, catches_fire_from_lava]
	#initialize blockInfo (info box info appended later)
	blockInfo = [False, link, "?", "?", "?", "?"]
	#navigate the block's page
	req = requests.get(link, allow_redirects=True)
	contents = req.text
	soup = BeautifulSoup(contents, 'html.parser')
	#check if there is a redirect
	redirectInfo = get_redirect_info(soup, link)
	blockInfo[blockValueDict["Is redirect"]-parameterShift]=redirectInfo[0]
	blockInfo[blockValueDict["Redirect url"]-parameterShift]=redirectInfo[1]
	#get data from History table
	historyTable = soup.find('table', {"data-description": "History"})
	if historyTable:
		blockInfo[2] = get_version_added(historyTable)
		if findVersionRemoved:
			blockInfo[3] = get_version_removed(historyTable)
	#get data from info box
	infoBox = soup.find('table', "infobox-rows")
	if infoBox:
		infoBoxInfo = get_block_info_box_info(infoBox)
		blockInfo.extend(infoBoxInfo)
	#return the needed data as a list
	return blockInfo
def get_block_info_box_info(infoBox, parameterShift=9):
	# returns [rarity_tier, renewable, stackable, tool, blast_resistance,
	# hardness, luminous, transparent, waterloggable, flammable, catches_fire_from_lava]
	info = ["?", "?", "?", "?", "?", "?", "?", "?", "?", "?", "?"]
	infoBoxValueAreas = infoBox.find_all('tr')
	for valueArea in infoBoxValueAreas:
		valueAreaInfo = get_info_box_value_area_info(valueArea)
		valueTitle = valueAreaInfo[0]
		value = valueAreaInfo[1]
		if valueTitle in blockValueDict.keys():
			info[blockValueDict[valueTitle]-parameterShift] = value
		else:
			print("Unknown block value title of ", valueTitle)
	return info
#funcs called on item or block page
def get_redirect_info(soup, link):
	newLink = get_canonical_link(soup)
	isRedirect = False
	if newLink!=link:
		isRedirect = True
	return [isRedirect, newLink]
def get_info_box_value_area_info(valueArea):
	#returns an array [title,value] to indicate what this area is for and the value it holds
	#valueArea is a <tr> tag inside of the info box
	title = valueArea.th.text.replace('\n', '') #.text ignores <a> tags
	try:
		value = valueArea.p.text.replace('\n', '').encode('ascii', 'ignore').decode(
			'ascii')  # removes invalid ascii characters
	except AttributeError:
		value = ""
		print("Info box area could not be parsed for: ", title)
	if (title=="Tool" or title=="Tools") and value=="":
		#Tool section displays an image with a link to the corresponding tool, so we must extract this info from the link
		toolLinkTags = valueArea.p.find_all('a')
		for linkTagIndex in range(len(toolLinkTags)):
			toolLink = toolLinkTags[linkTagIndex]['href']
			if toolLink in toolDict.keys():
				if linkTagIndex==0:
					value = toolDict[toolLink]
				else:
					value += ", " + toolDict[toolLink]
			else:
				print("Unknown tool link of ", toolLink)
	return [title, value]
def get_version_added(historyTable):
	historyRows = historyTable.find_all('tr')
	edition = ""
	version = ""
	for	rowIndex in range(len(historyRows)):
		row = historyRows[rowIndex]
		#check if this is an edition header row
		if ('class' in row.attrs) and ("collapsible" in row['class']):
			#this row is an edition header row
			edition = row.text if (row.text in javaEditions) else ""
			continue
		elif edition=="":
			#This is a version row but no edition has been set
			#cannot set edition before version
			continue
		elif len(row.find_all('a'))>0:#version must be a link
			#This is a version row and edition has been set
			#check if this is an external link (AKA, not a valid version)
			if ('class' in row.a.attrs) and ("external" in row.a['class']):
				continue
			else:
				#check if this is a valid version
				if is_version_valid(row.a.text, edition):
					version = row.a.text
					break
	if version=="":
		#we found no version, reset the vars
		edition = "Unknown Edition"
		version = "Unknown Version"
	return edition + " " + version
def get_version_removed(historyTable):
	historyRows = historyTable.find_all('tr')
	edition = ""#stores the latest java edition found
	version = ""#stores the latest java version found
	currentEdition = ""#stores the java edition we are currently cycling through
	for	rowIndex in range(len(historyRows)):
		row = historyRows[rowIndex]
		#check if this is an edition header row
		if ('class' in row.attrs) and ("collapsible" in row['class']):
			#this row is an edition header row
			currentEdition = row.text if (row.text in javaEditions) else ""
			continue
		elif currentEdition=="":
			#This is a version row but no edition has been set
			#cannot set edition before version
			continue
		else:
			#This is a version row and currentEdition has been set
			#check if this is an external link (AKA, not a valid version)
			if row.a:
				if ('class' in row.a.attrs) and ("external" in row.a['class']):
					#this is an external link, aka, not a valid version row
					continue
				else:
					#check that this is a valid version
					if is_version_valid(row.a.text, currentEdition):
						edition = currentEdition
						version = row.a.text
			else:
				#this row does not have a link, so it is not a valid version
				continue
	if version=="":
		#we found no version, reset the vars
		edition = "Unknown Edition"
		version = "Unknown Version"
	return edition + " " + version
def is_version_valid(version,edition):
	#checks if this version name is a valid version name for this edition. Not 100% accurate
	match edition:
		case "Java Edition pre-Classic":
			if version[:3]=="rd-" or version=="Cave game tech test":
				return True
		case "Java Edition Classic":
			if version[:2]=="0." or version[:3]=="mc-":
				return True
		case "Java Edition Indev":
			if version[:3]=="201" or version[:4]=="0.31":
				return True
		case "Java Edition Infdev":
			if version[:5]=="20100":
				return True
		case "Java Edition Alpha":
			if version[:3]=="v1.":
				return True
		case "Java Edition Beta":
			if version[:2] == "1.":
				return True
		case "Java Edition":
			if version[:2]=="1.":
				return True
		case _:
			print("Unknown edition: ", edition)
			#really this shouldn't ever trigger. I have an equivalent check when setting edition
			return False
	return False
#helper
def get_canonical_link(soup):#returns the link after a redirect on minecraftwiki
	canonical = soup.find('link', {'rel': 'canonical'})
	return canonical['href']

if __name__=="__main__":
	main()
