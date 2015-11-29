# Author Alex T. - Github: github.com/altzan
# import the necessary packages
import argparse
import cv2
import numpy as np
import psycopg2
from math import sqrt
import mahotas
import os

def checkTable():# Check if table exists. If not we are creating it.  
    cursor.execute("select exists(select * from information_schema.tables where table_name=%s)", ('index',))
    flag=cursor.fetchone()[0]
    if flag==False:
        cursor.execute('''CREATE TABLE index
        (image TEXT PRIMARY KEY NOT NULL,
        color TEXT,
        shape TEXT,
        texture TEXT)''')
        print "Table created successfully"
    conn.commit()
    
def chi2_distance( histA, histB, eps = 1e-10):# Calculate distance detween vectors
        # compute the chi-squared distance
    d = 0.5 * np.sum([((a - b) ** 2) / (a + b + eps)
        for (a, b) in zip(histA, histB)])
 
        # return the chi-squared distance
    return d
    
def euclidean_distance( a, b):
    return sqrt(sum( (a - b)**2 for a, b in zip(a, b)))

def index(path):# Add the directory or the image in the database
    # If it is a directory
    if os.path.isdir(path):
        
        try:
            cursor.execute("SELECT image from index")
        except:
            print "Error while trying to SELECT from index"
          
        keys = []  
        rows = cursor.fetchall()
        for row in rows:
            keys.extend(row)
        files = os.listdir(path)
        # Loop over images in the directory
        for imagePath in files:
            imagePath = os.path.join(path,imagePath)
            flag = False
            if imagePath in keys:
                flag = True
            
            if flag:
                print "Image ",imagePath," already exists in the Database!"
                continue
                
            try:
                # Extract the image filename and load it
                imageID = imagePath[imagePath.rfind("/") + 1:]
                image = cv2.imread(imagePath)
                # Find the histogram of the image
                color = describeColor(image)
                # Find the shape of the image
                shape = describeShape(image)
                # Find the texture of the image
                texture = describeTexture(image)
                shape = [str(f) for f in shape]
                color = [str(f) for f in color]
                texture = [str(f) for f in texture]
            except:
                print "Error while extracting features. Only images allowed!"
                continue
            cursor.execute("INSERT INTO index (image, color, shape, texture) VALUES (%s, %s, %s, %s);", 
                          (imageID, " ".join(color), " ".join(shape), " ".join(texture)))
            conn.commit()
            print "Indexing of image: ",imagePath," completed!"
          
    # If it is a file   
    elif os.path.isfile(path):
        
        cursor.execute("SELECT EXISTS(SELECT * FROM index WHERE image=%s)", (path,))
        flag=cursor.fetchone()[0]
            
        if flag:
            print "Image ",path," already exists in the Database!"
            return
        try:
            image = cv2.imread(path)
            # Find the histogram of the image
            color = describeColor(image)
            # Find the shape of the image
            shape = describeShape(image)
            # Find the texture of the image
            texture = describeTexture(image)
            shape = [str(f) for f in shape]
            color = [str(f) for f in color]
            texture = [str(f) for f in texture]
        except:
            print "Error while extracting features. Only images allowed!"
            return
        try:
            cursor.execute("INSERT INTO index (image, color, shape, texture) VALUES (%s, %s, %s, %s);",  
                       (path, " ".join(color), " ".join(shape), " ".join(texture)))
            conn.commit()
            print "Indexing of image: ",path," completed!"
        except:
            print "Error while saving to DB! Maybe the image you are trying to save already exists in the table!"

    print "Indexing completed."

def search( queryColor, queryShape, queryTexture):
    # Initialize our dictionary of results
    results = {}
    dColor = {}
    dShape = {}
    dTexture = {}
            
    try:
        cursor.execute("SELECT * from index")
    except:
        print "Error while trying to SELECT from index"

    rows = cursor.fetchall()
   
    featuresColor=map(float, rows[0][1].split())
    featuresShape=map(float, rows[0][2].split())
    featuresTexture=map(float, rows[0][3].split())
    maxdColor = chi2_distance(featuresColor, queryColor)
    mindColor = chi2_distance(featuresColor, queryColor)
    maxdShape = chi2_distance(featuresShape, queryShape)
    mindShape = chi2_distance(featuresShape, queryShape)
    maxdTexture = chi2_distance(featuresTexture, queryTexture)
    mindTexture = chi2_distance(featuresTexture, queryTexture)
    
    # Find the distances and the max and min of each feature
    for row in rows:
        featuresColor=map(float, row[1].split())
        featuresShape=map(float, row[2].split())
        featuresTexture=map(float, row[3].split())
        dColor[row[0]] = chi2_distance(featuresColor, queryColor)
        dShape[row[0]] = chi2_distance(featuresShape, queryShape)
        dTexture[row[0]] = chi2_distance(featuresTexture, queryTexture)
        if dColor[row[0]] > maxdColor:
            maxdColor=dColor[row[0]]
        elif dColor[row[0]] < mindColor:
            mindColor=dColor[row[0]]
        if dShape[row[0]] > maxdShape:
            maxdShape = dShape[row[0]]
        elif dShape[row[0]] < mindShape:
            mindShape = dShape[row[0]]
        if  dTexture[row[0]] > maxdTexture:
            maxdTexture = dTexture[row[0]]
        elif dTexture[row[0]] < mindTexture:
            mindTexture = dTexture[row[0]]
    
    # Normalize the features            
    for i in rows:
        if maxdColor-mindColor != 0.0:
            dColor[i[0]]=(dColor[i[0]]-mindColor)/(maxdColor-mindColor)
        if maxdShape-mindShape != 0.0:
            dShape[i[0]]=(dShape[i[0]]-mindShape)/(maxdShape-mindShape)
        if maxdTexture-mindTexture != 0.0:
            dTexture[i[0]]=(dTexture[i[0]]-mindTexture)/(maxdTexture-mindTexture)
        
    for row in rows:
        results[row[0]] = dColor[row[0]] + dShape[row[0]] + dTexture[row[0]]

    # Return our (limited) results
    
    return results
    

def describeTexture(image):# Find the texture of the image
    height, width = image.shape[:2]
    if height * width > 1024 * 768:
        image = cv2.resize(image, (1024,768))
    return mahotas.features.haralick(image).mean(0)

def describeShape(image):# Find the shape of the image
    height, width = image.shape[:2]
    if height * width > 1024 * 768:
        image = cv2.resize(image, (1024,768))
    image = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    
    # Return the Zernike moments for the image
    return mahotas.features.zernike_moments(image, 50, 8) 

def describeColor(image):# Find the color of the image
    # Convert the image to the HSV color space and initialize
    # the features used to quantify the image
    image = cv2.cvtColor(image, cv2.COLOR_BGR2HSV)
    (h, w) = image.shape[:2]
    if h * w > 1024 * 768:
        image = cv2.resize(image, (1024,768))
    features = []

    (cX, cY) = (int(w * 0.5), int(h * 0.5))
    # Divide the image into four rectangles/segments (top-left,
    # top-right, bottom-right, bottom-left)
    segments = [(0, cX, 0, cY), (cX, w, 0, cY), (cX, w, cY, h),
        (0, cX, cY, h)]

    # Construct an elliptical mask representing the center of the
    # image
    (axesX, axesY) = (int(w * 0.75) / 2, int(h * 0.75) / 2)
    ellipMask = np.zeros(image.shape[:2], dtype = "uint8")
    cv2.ellipse(ellipMask, (cX, cY), (axesX, axesY), 0, 0, 360, 255, -1)

    # Loop over the segments
    for (startX, endX, startY, endY) in segments:
        # Construct a mask for each corner of the image, subtracting
        # the elliptical center from it
        cornerMask = np.zeros(image.shape[:2], dtype = "uint8")
        cv2.rectangle(cornerMask, (startX, startY), (endX, endY), 255, -1)
        cornerMask = cv2.subtract(cornerMask, ellipMask)

        # Extract a color histogram from the image, then update the
        # feature vector
        hist = histogram(image, cornerMask)
        features.extend(hist)

    # Extract a color histogram from the elliptical region and
    # update the feature vector
    hist = histogram(image, ellipMask)
    features.extend(hist)

    # Return the feature vector
    return features

def histogram(image, mask):# Calculate the histogram
    # Extract a 3D color histogram from the masked region of the
    # image, using the supplied number of bins per channel; then
    # normalize the histogram
    hist = cv2.calcHist([image], [0, 1, 2], mask, (8, 12, 3),
        [0, 180, 0, 256, 0, 256])
    hist = cv2.normalize(hist).flatten()

    # Return the histogram
    return hist

# PROGRAM BEGINS HERE
# Construct the argument parser and parse the arguments
ap = argparse.ArgumentParser()
ap.add_argument("-j", "--job", required = True, 
    help= "Path to directory or image")
ap.add_argument("-p", "--path", required = False,
    help = "Path to the query image or path to directory for indexing")
ap.add_argument("-l", "--limit", required = False,
    help = "Number of results")
ap.add_argument("-d", "--drop", required = False,
    help = "[y/n] - Drop existing table. Create a new one for the directory")
args = vars(ap.parse_args())

with open('dbconf.txt', 'r') as f:
    first_line = f.readline()

try:    
    dbsettings=first_line.split(',')
except:
    print "There is an error in the 'dbconf.txt' file"

try:
    conn=psycopg2.connect("dbname='"+dbsettings[0]+"' user='"+dbsettings[1]+"' password='"+dbsettings[2]+"'")
    cursor=conn.cursor()
except:
    print "There is an error in the specified database connection settings."    
 
# Check if the argument is 'search'
if args["job"]=="search":
    if args["limit"].isdigit() and args["limit"]>0:
        limit=int(args["limit"])
    else:
        limit="all"
    query = cv2.imread(args["path"])
    # Extract the features of the query image
    featuresColor = describeColor(query)
    featuresShape = describeShape(query)
    featuresTexture = describeTexture(query)
    # Write statistics to file
    statistics = open('statistics.txt','w')
    statistics.write(','.join(str(s) for s in featuresColor) + '\n')
    statistics.write(','.join(str(s) for s in featuresShape) + '\n')
    statistics.write(','.join(str(s) for s in featuresTexture))
    statistics.close()
    # Perform the search
    results = search(featuresColor,featuresShape, featuresTexture)
        
    f = open('results.txt','w')
    # Sort the results, so that the smaller distances (i.e. the
    # more relevant images are at the front of the list)
    results = sorted([(v, k) for (k, v) in results.items()])
    if limit!="all":
        results=results[:limit]
    for x in results:
        # Write line to file
        f.write(' '.join(str(s) for s in x) + '\n') 
        print x  
        
    # Close file   
    f.close()
     
elif args["job"]=="rel":
    array = []
    if args["limit"].isdigit() and args["limit"]>0:
        limit=int(args["limit"])
    else:
        limit="all"
        
    fColorAdded=[0]
    fShapeAdded=[0]
    fTextureAdded=[0]
    nFiles=0
    
    with open("relevance.txt", "r") as f:
        for line in f:
            array.append(line.rstrip('\r\n'))
            cursor.execute("SELECT * FROM index WHERE image=%s", (line.rstrip('\r\n'),))# Get images from table
            rows = cursor.fetchall()
            featuresColor = map(float, rows[0][1].split())# Convert to float
            featuresShape = map(float, rows[0][2].split())
            featuresTexture = map(float, rows[0][3].split())
            fColorAdded = np.add(fColorAdded, featuresColor)# Add the features of the images
            fShapeAdded = np.add(fShapeAdded, featuresShape)
            nFiles += 1
    f.close() 
    fTextureAdded = np.add(fTextureAdded, featuresTexture)# Take the mean of all images
    fColorAdded = [i/nFiles for i in fColorAdded]
    fShapeAdded = [i/nFiles for i in fShapeAdded]
    fTextureAddded = [i/nFiles for i in fTextureAdded]
    results = search(fColorAdded,fShapeAdded, fTextureAdded)
    
    f = open('results.txt','w')
    for relImg in array:
        try:
            del results[relImg]
        except:
            pass
    # Sort the results, so that the smaller distances (i.e. the
    # more relevant images are at the front of the list)
    results = sorted([(v, k) for (k, v) in results.items()])
    if limit!="all":
        results=results[:limit]
    for x in results:
        # Write line to file
        f.write(' '.join(str(s) for s in x) + '\n') 
        print x
        
    # Close file
    f.close() 
# Check if the argument is 'index'
elif args["job"]=="index":
    if args["drop"]=="y" or args["drop"]=="yes" :
        cursor.execute("DROP TABLE IF EXISTS index")
        conn.commit
    checkTable()
    index(args["path"])
    
conn.close()  
