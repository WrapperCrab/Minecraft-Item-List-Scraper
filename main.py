from bs4 import BeautifulSoup
import csv
import requests

def main():
	blocksLink = "https://minecraft.wiki/w/Block"
	itemsLink = "https://minecraft.wiki/w/Item"
	create_blocks_csv(blocksLink, 30, 23)


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
	"Blast resistance": 13,
	"Hardness": 14,
	"Luminous": 15,
	"Transparent": 16,
	"Waterloggable": 17,
	"Flammable": 18,
	"Catches fire from lava": 19
}
toolDict = {#link extension: tool name
	"/w/Axe": "Axe",
	"/w/Pickaxe": "Pickaxe",
	"/w/Wooden_Pickaxe": "Wooden Pickaxe",
	"/w/Stone_Pickaxe": "Stone Pickaxe",
	"/w/Iron_Pickaxe": "Iron Pickaxe",
	"/w/Diamond_Pickaxe": "Diamond Pickaxe",
	"/w/Shovel": "Shovel"
}
javaEditions = ["Java Edition pre-Classic", "Java Edition Classic", "Java Edition Indev",
				"Java Edition Infdev", "Java Edition Beta", "Java Edition"]

def create_blocks_csv(link, numAnalyze=0, numSkip=0):
	#create the output csv
	with open("blocks.csv", 'w', newline='') as file:
		writer = csv.writer(file)
		#generate header line of csv
		field = []
		for index in range(len(blockValueDict.values())):
			header = list(blockValueDict.keys())[list(blockValueDict.values()).index(index)]
			field.append(header)
		writer.writerow(field)
		#navigate the main page
		req = requests.get(link)
		contents = req.text
		soup = BeautifulSoup(contents, 'html.parser')
		#iterate over the blocks
		blocksDiv = soup.find(id="List_of_blocks").parent.next_sibling.next_sibling
		blocks = blocksDiv.ul.find_all("li")#entries
		for blockIndex in range(len(blocks)):
			#handle skip and exit conditions
			if numSkip:
				if blockIndex<numSkip:
					continue
			if numAnalyze:
				if blockIndex-numSkip>=numAnalyze:
					return
			print(blockIndex)#!!!debug
			#find block info
			block = blocks[blockIndex]
			imageUrl = block.find_all("a", "mw-file-description")[0]['href']
			blockLinkTag = block.find_all("a")[1]#returns the a tag that contains the link to its page
			pageUrl = blockLinkTag['href']
			blockName = blockLinkTag.contents[0]
			#get data from block's page
			blockInfo = get_block_info("https://minecraft.wiki"+pageUrl)
			#add data to the csv
			csvLine = [blockName, imageUrl, pageUrl]
			csvLine.extend(blockInfo)
			writer.writerow(csvLine)
	print("block.csv successfully created!")

def get_block_info(link, parameterShift=3): #returns [is_redirect, redirect_url, version_added, version_removed,
	#obtainable, craftable, rarity_tier, renewable, stackable, tool, blast_resistance,
	#hardness, luminous, transparent, waterloggable, flammable, catches_fire_from_lava]
	#initialize blockInfo
	blockInfo = [False, link, "?", "?", "?", "?", "?", "?", "?", "?", "?", "?", "?", "?", "?", "?", "?"]
	#check if there is a redirect
	req = requests.get(link, allow_redirects=True)
	contents = req.text
	soup = BeautifulSoup(contents, 'html.parser')
	newLink = get_canonical_link(soup)
	isRedirect = False
	if newLink!=link:
		isRedirect = True
	blockInfo[blockValueDict["Is redirect"]-parameterShift]=isRedirect
	blockInfo[blockValueDict["Redirect url"]-parameterShift]=newLink
	#get data from info box
	infoBox = soup.find('table', "infobox-rows")
	infoBoxValueAreas = infoBox.find_all('tr')
	for valueArea in infoBoxValueAreas:
		valueAreaInfo = get_info_box_value_area_info(valueArea)
		valueTitle = valueAreaInfo[0]
		value = valueAreaInfo[1]
		if valueTitle in blockValueDict.keys():
			if valueTitle in blockValueDict.keys():
				blockInfo[blockValueDict[valueTitle]-parameterShift] = value
			else:
				print("Unknown block value title of ", valueTitle)
	#get data from History table
	historyTable = soup.find('table', {"data-description": "History"})

	#1) find the first Java edition section of the table
	#2) find the version title in the first entry of this section
	# print(historyTable.prettify())#!!!debug

	historyRows = historyTable.find_all('tr')
	edition = ""
	version = ""
	for	rowIndex in range(len(historyRows)):
		row = historyRows[rowIndex]
		# print(row.prettify())#!!!debug
		#check if this is an edition header row
		if ('class' in row.attrs) and ("collapsible" in row['class']):
			#this row is an edition header row
			edition = row.text if (row.text in javaEditions) else ""
			continue
		elif edition=="":
			#This is a version row but no edition has been set
			#cannot set edition before version
			continue
		else:
			#This is a version row and edition has been set
			#check if this is an external link (AKA, not a valid version)
			if ('class' in row.a.attrs) and ("external" in row.a['class']):
				continue
			else:
				version = row.a.text
				break
	if version=="":
		#we found no version, reset the vars
		edition = "Unknown Edition"
		version = "Unknown Version"
	blockInfo[2] = edition + " " + version
	print(blockInfo[2])#!!!debug

	craftable = 1

	#return the needed data as a list
	return blockInfo

def get_info_box_value_area_info(valueArea):
	#returns an array [title,value] to indicate what this area is for and the value it holds
	#valueArea is a <tr> tag inside of the info box
	title = valueArea.th.text.replace('\n', '') #.text ignores <a> tags
	value = valueArea.p.text.replace('\n', '')
	if title=="Tool" and value=="":
		#Tool section displays an image with a link to the corresponding tool, so we must extract this info from the link
		toolLink = valueArea.p.find('a')['href']
		if toolLink in toolDict.keys():
			value = toolDict[toolLink]
		else:
			print("Unknown tool link of ", toolLink)
	else:
		value = valueArea.p.text.replace('\n', '').encode('ascii','ignore')
	return [title, value]

def get_canonical_link(soup):#returns the link after a redirect on minecraftwiki
	canonical = soup.find('link', {'rel': 'canonical'})
	return canonical['href']

if __name__=="__main__":
	main()
