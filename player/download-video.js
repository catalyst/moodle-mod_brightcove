videojs.plugin('downloadVideoPlugin', function() {

    // Create variables and new div, anchor and image for download icon.
    var myPlayer = this,
        videoName,
        totalRenditions,
        mp4Ara = [],
        highestQuality,
        spacer,
        newElement = document.createElement('div'),
        newLink = document.createElement('a'),
        newImage = document.createElement('img');

    myPlayer.on('loadstart',function(){
        // Reinitialize array of MP4 renditions in case used with playlist.
        // This prevents the array having a cumulative list for all videos in playlist.
        mp4Ara = [];
        // Get video name and the MP4 renditions.
        videoName = myPlayer.mediainfo['name'];
        rendtionsAra = myPlayer.mediainfo.sources;
        totalRenditions = rendtionsAra.length;
        for (var i = 0; i < totalRenditions; i++) {
            if (rendtionsAra[i].container === "MP4" && rendtionsAra[i].hasOwnProperty('src')) {
                mp4Ara.push(rendtionsAra[i]);
            };
        };
        // Sort the renditions from highest to lowest.
        mp4Ara.sort( function (a,b){
            return b.size - a.size;
        });
        // Set the highest rendition.
        highestQuality = mp4Ara[0].src;

        downloadString = "<a href='" + highestQuality + "' download='" + videoName + "'>Download the Video</a>";
        document.getElementById('insertionPoint').innerHTML = downloadString;
    })
});
