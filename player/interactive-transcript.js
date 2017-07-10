videojs.plugin('interactiveTranscriptPlugin', function() {
    // Create variables and new div, anchor and image for download icon.
    var myPlayer = this;
    myPlayer.on('loadstart',function(){
        // Set up any options.
        var options = {
          showTitle: false,
          showTrackSelector: false,
        };
     // Initialize the plugin.
        var transcript = myPlayer.transcript(options);

        overlay = document.createElement('track');
        overlay.kind = 'captions';
        overlay.src = 'http://brightcove.vo.llnwd.net/v1/unsecured/media/5483960634001/201706/212/5483960634001_3a9acb3d-2e23-403c-b58c-fdf0a02b8fd3.vtt?pubId=5483960634001&videoId=5488076477001';
        overlay.srclang="en" 
        overlay.label="English"
        myPlayer.el().getElementsByTagName("video")[0].appendChild(overlay);

        // Then attach the widget to the page.
        var transcriptContainer = document.querySelector('#transcript');
        transcriptContainer.appendChild(transcript.el()); 
    })
});
