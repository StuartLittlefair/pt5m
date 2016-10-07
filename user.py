import json
import urllib2,urllib
import json

def getPage(url,data=None):
    if data:
        data = urllib.urlencode(data)
        req  = urllib2.Request(url,data)
    else:
        req = urllib2.Request(url)
    response = urllib2.urlopen(req,timeout=4)
    return response.read()

data={}
data['pID'] = 1191
jsonResp = getPage("http://slittlefair.staff.shef.ac.uk/pt5m/whichUser.php",data=data)
result = json.loads(jsonResp)
print result['userID']