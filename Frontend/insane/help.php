<html>
    <head>
        <meta charset="utf-8">
        <title>Help guide</title>
        <script type="text/javascript">
            document.title = "IIS | Help guide"
        </script>
    </head>
    <body>
        
        <div id="title">Help guide</div>
        <div id="details5">Insane Image Search is a simple and minimal search engine for pictures. It implements search for
        <br/>a single image, positive relevance feedback, advanced UI features regarding the draw and
        <br/>the render of the image histogram of a selected image -search or result- as also as the ability to
        <br/>upload and expand the images dataset by adding new pictures.
        </div>
        
        <h3>Image types</h3>
        <div id="details5">
            The Insane Image Search (IIS) tool supports both JPG and PNG file types. There's no limitation in filename extentions
            <br/>regarding the case sensitivity as also as the synonyms (JPG, JPEG etc). You have to note, though, that apart from
            <br/>these two file types, none other is supported. 
        </div>
        
        <h3>Search result page</h3>
        <div id="details5">
            The user is presented with a simple result page, after the upload of the search image. On the left, one can view
            <br/>the search image <b>(1)</b> along with some useful information (width, height, image type etc) <b>(2)</b>. If the user wishes,
            <br/>he can click on "Draw image's histogram" which will redirect him in a page that can view the search image's
            <br/>histogram (more info on that on the specific category).
            <br/>On the right, the user can view the result images returned for the specific image query <b>(3)</b>. For relevance feedback
            <br/>he can select any image by clicking in the corresponding checkbox on the right of every picture <b>(4)</b>
            <br/>as also as he can view the image's histogram along with the query by clicking any image in the results. Finally,
            <br/>the user can expand the search results table by clicking on the arrows icon <b>(5)</b>.
            <br/><img src="images/help-img.png" width="500px"/>
        </div>
        
        <h3>Relevance feedback</h3>
        <div id="details5">
            The IIS implements a relevance feedback fucntion. Only positive feedback is considered (the user cannot mark a picture
            <br/>as non-relevant). The user simply clicks up to <b>three</b> images in the search results and the backend in Python, recalculates
            <br/>the query taking into account this relevant pictures. There's no limitation on how many times a user can resupply
            <br/>a query into the relevance feedback function but obviously after a number of times the user keeps taking the same
            <br/>set of pictures.
        </div>
        
        <h3>Histogram of images</h3>
        <div id="details5">
            The user can view an image's histogram to gather information about the colors, hue, saturation etc in much more visual
            <br/>form. He can select to view the default histogram for RGB, Hue, Saturation and other options. The user is supplied
            <br/>with a large amount of options to render the histogram as he wishes. Note that if a user chooses to display the
            <br/>histogram of an image from search results, on the right he will get the histogram of the search image too.
        </div>
    </body>
</html>