#!/usr/bin/python3
# -*-coding:Utf-8 -*

import sys
import pickle
import re
import json
from time import time
from urllib.request import urlopen
from xml.dom import minidom
from os import path
from os import listdir


dataUpdate = False
if sys.argv[1] == 'update':
  dataUpdate = True
  del sys.argv[1]

exit=False
myurl="http://192.168.0.1/api/1.0/?method=lan.getHostsList"
try:
  if str(sys.argv[1]).startswith("http://"):
    myurl = sys.argv[1]
  elif re.match(r"^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$", str(sys.argv[1])) is not None:
    myurl = "http://" + sys.argv[1] + "/api/1.0/?method=lan.getHostsList"
  elif sys.argv[1] == "ok":
    exit=True
except:
  pass

if exit:	# Test pour l'install
  print("ok")
  sys.exit(1)


# Definition des variables

dirPath = path.dirname(path.realpath(__file__)) + "/"
timestamp=int(time())


# Récupération de la config

config = {}
for lsfile in listdir(dirPath):
  if lsfile.startswith("CONFIG"):
    with open(dirPath+lsfile, 'r') as file:
      for line in file:
        (key, value) = line.split()
        config[key] = value


def recupdump():        # Fonction d'importation des donnees
  if path.exists(dirPath+"hosts_dump"):
    with open(dirPath+"hosts_dump", "rb") as output:
      oldtimestamp, hosts = pickle.load(output)
  else:
    oldtimestamp=0
    hosts=[]
  return hosts, oldtimestamp

def savedump(): # Fonction de sauvegarde des donnees
  for elt in hosts:
    elt.isUpdate = False
  with open(dirPath+"hosts_dump", "wb") as output:
    pickle.dump((timestamp,hosts), output)

def importxml(url):	# Fonction d'importation du xml
  try:
    file = urlopen(url)
  except OSError:
    print("Echec de recuperation du xml")
    sys.exit(1)
  with open(dirPath+'hosts.xml', 'wb') as output:
    output.write(file.read())
  doc = minidom.parse(dirPath+'hosts.xml')
  return doc

def parseXml(xml):
  root = xml.documentElement
  current = root.firstChild
  while current.nextSibling:	# pour chaque noeud
    if current.attributes:	# si on trouve des attributs xml
      data = {}
      for attr in current.attributes.values():	# on les stock dans un dictionnaire
        data[attr.nodeName] = attr.nodeValue
      for elt in hosts:
        if elt.mac == data["mac"]:	# si l'objet existe deja on l'udpate et on le supprime les données
          elt.update(data)
          data = {}
          break
      if data != {}:	# si les données n'ont pas été supprimées on l'ajoute aux hosts
        hosts.append(host(data))
    current=current.nextSibling
  return hosts

class host:
  def __init__(self, dictAttr):
    self.dictAttr = dictAttr
    self.offline = 0
    self.online = 0
    self.keepalive = 900
    self.timer = 0
    self.isUpdate = True
    self.isLock = False
    if self.dictAttr.get("status") == "offline":
      self.active = False
    else:
      self.active = True

  def __getattr__(self, key):
    if key.startswith('__') and key.endswith('__'):	# Obligatoire pour pickle
      return super(host, self).__getattr__(key)
    return self.dictAttr.get(key)

  def __setattr__(self, key, value):
    object.__setattr__(self, key, value)

  def update(self, dictAttr):
    self.isLock = False		# MAJ du keepalive et lock
    self.keepalive = 900
    for elt in config:
      if elt == self.mac:
        self.keepalive = int(config[elt])
        self.isLock = True
    if (self.active) and (dictAttr.get("status") == "online" or dictAttr.get("alive") != self.dictAttr.get("alive")):	# si active => active
      self.timer = 0
      self.active = True
      self.online += (timestamp - oldtimestamp)
    elif self.active == False and dictAttr.get("alive") == self.dictAttr.get("alive"):	# si !active => !active
      self.offline += (timestamp - oldtimestamp)
    elif self.active == True and dictAttr.get("alive") == self.dictAttr.get("alive"):	# si active => !active
      self.timer += (timestamp - oldtimestamp)
      if self.timer >= self.keepalive:
        self.active = False
        self.offline = self.timer
        self.online -= (self.timer - (timestamp - oldtimestamp))
        self.timer = 0
      else:
        self.online += (timestamp - oldtimestamp)
    elif self.active == False and dictAttr.get("alive") != self.dictAttr.get("alive"):	# si !active => active
      self.active = True
      self.online = 0
      self.offline += (timestamp - oldtimestamp)
      self.timer = 0
    for key in dictAttr:	#MAJ des nouvelles valeurs
      self.dictAttr[key] = dictAttr.get(key)
    self.isUpdate = True


# Execution du programme

hosts, oldtimestamp = recupdump()	# On recupere les anciennes donnees

if dataUpdate:
  doc = importxml(myurl)	# On importe le xml
  hosts = parseXml(doc)		# On MAJ les donnees avec le xml
  missingHosts = [x for x in hosts if not x.isUpdate and x.isLock]	# On met a jour les host verouillés manquants
  for elt in missingHosts:
    elt.update({"status":"offline", "alive":elt.alive})
  hosts = [x for x in hosts if x.isUpdate or x.isLock]	# On supprime les hosts qui ne sont plus presents
  savedump()


serial = {}
for elt in hosts:
  serial[elt.mac] = {
    "name":elt.name,
    "iface":elt.iface,
    "ip":elt.ip,
    "status":elt.status,
    "active":elt.active,
    "online":elt.online,
    "offline":elt.offline,
    "timer":elt.timer,
    "keepalive":elt.keepalive,
    "isLock":elt.isLock
}
                    
print(json.dumps(serial, indent=4))

