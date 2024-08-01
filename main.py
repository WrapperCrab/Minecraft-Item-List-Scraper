import requests
def main():
    r = requests.get('https://www.geeksforgeeks.org/python-programming-language/')
    print(r)
    print(r.content)


if __name__=="__main__":
    main()