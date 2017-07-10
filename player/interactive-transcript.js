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
        var transcriptUrl = myPlayer.el().dataset.captions

        var overlay = document.createElement('track');
        overlay.kind = 'captions';
        overlay.src = transcriptUrl;
        overlay.srclang="en" 
        overlay.label="English"
        myPlayer.el().getElementsByTagName("video")[0].appendChild(overlay);

        // Then attach the widget to the page.
        var transcriptContainer = document.querySelector('#transcript');
        transcriptContainer.appendChild(transcript.el()); 
    })
});
