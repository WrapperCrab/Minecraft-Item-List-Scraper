import csv
import requests

# for each block in blocks.csv and item in items.csv, download the

def main():
    print("starting download")
    download_images("blocks.csv", True)
    download_images("items.csv", False)
    print("images finished downloading")

def download_images(csvPath, isBlocks=True, numDownload=0):
    leftDownload = numDownload
    imageDomain = "BlockSprite_" if isBlocks else "ItemSprite_"
    with open(csvPath, 'r') as file:
        reader = csv.reader(file)
        for row in reader:
            name = row[0]
            if (name=="Name"):
                continue
            nameFormatted = name.lower().replace('waxed ','').replace('(','').replace(')','').replace(' ','-')
            #download the sprite from minecraftWiki
            SpriteLink = "https://minecraft.wiki" + "/images/" + imageDomain + nameFormatted + ".png"

            headers = {'User-Agent' : 'MowBot/1.0 (winter@starcrossonline.com)',}
            data = requests.get(SpriteLink, allow_redirects=False, headers=headers).content

            imgFile = open('../Icons/' + name + '.png','wb')
            imgFile.write(data)
            imgFile.close()

            print("Downloaded sprite for " + name)

            leftDownload-=1
            if (numDownload and leftDownload<=0):
                return
        file.close()

if __name__=="__main__":
    main()
