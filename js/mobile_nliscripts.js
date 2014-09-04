$(document).on("touchstart click", '.band', function(){
    if(!$(this).hasClass('slideout') || !$(this).hasClass('slideout')) {    
        var thisid = this.id;
        var artistid = thisid.split("-")[1];
        var wrappernumber = $(this).attr("wrappernumber");
        $('#close' + artistid).delay(400).fadeIn();
        $('#wrong' + artistid).delay(400).fadeIn();
        getArtistData(artistid,wrappernumber);
        
    }
});

$('.close').click(function(e) {
    e.preventDefault();
});

function closeWrongFunction() {
    $(document).on("touchstart click", function(event){
        if(!$(event.target).closest('#wrong_popup').length) {
            $("#wrong_popup").fadeOut("medium", function(){ this.remove();});
        }
    });
}

$(document).on("touchstart click", '.wrong', function() {
    var band = $(this).parent().parent().attr('band');
    var artistid = $(this).parent().parent().attr('artistid');
    $.when(wrongStuff(band,artistid).then(closeWrongFunction));

});

function wrongStuff(band,artistid) {
    $('body').append('<div id="wrong_popup" style="display:none;"></div>');
    $('#wrong_popup').html(
        '<span class="wrong_popup_title"><span class="wrongTitle">So you think somethings wrong?</span><br /> It probably is. Help me out.</span><br />'
        + '<span class="wrong_body"><span class="wrongBand">' + band + '</span><br /> Whats wrong? </span>'
        + "<form action='' name='wrong_stuff'><select id='wrong_select'><option value='0'>Select from...</option><option value='spotify'>Spotify Links</option><option value='bandcamp_url'>Bandcamp Link</option><option value='website'>Website</option><option value='youtube'>Youtube Video</option></select>Suggestion?: <input type=text id='wrong_suggestion'><input type=submit id='wrong_go' value='Go'></form>"

    );
    $('#wrong_popup').show();
    $('#wrong_go').click(function() {
        var element = $('#wrong_select').val();
        var suggestion = $('#wrong_suggestion').val();
        var clean_suggestion = encodeURIComponent(suggestion);
         $.ajax({
                cache: false,
                type: "POST",
                url: "includes/artistdata.php?action=wrongData",
                data: "artistid=" + artistid + "&element=" + element + "&suggestion=" + suggestion,
                dataType: "HTML",
                success: function (data) {
                    $('#wrong_popup').empty();
                    $('#wrong_popup').html('<h1 class="wrong_thanks">THANKS!</h1>');
                    setTimeout(function() {
                        $('#wrong_popup').fadeOut(800, function(){this.remove();});
                    }, 1400);
                    
                },
                error: function (xhr, ajaxOptions, thrownError) {
                }
          });
    });

}


$(document).on("touchstart click", function(event){
    if(!$(event.target).closest('#wrong_popup').length) {
        $("#wrong_popup").fadeOut("medium", function(){ this.remove();});
    }
});


function closeStuff() {
                $('.slided').fadeOut(300, function() { $(this).remove();$('.slideout').removeClass('slideout',600,'easeInOutQuad'); });
                $('.slideout').removeClass('slideout',600,'easeInOutQuad');
                $('.close').fadeOut(300, function() {$(this).hide();})
                $('.wrong').fadeOut(300, function() {$(this).hide();})
}

$( document ).on( 'keydown', function ( e ) {
    if ( e.keyCode === 27 ) { // ESC
        $(".hidden").fadeOut("medium", function(){ this.remove();});
        $("#pagehider").fadeOut("medium", function(){ this.remove();});
    }
});

function alphadropdownGo() {
    window.location = document.getElementById("alpha_dropdown").value;
}

function genredropdownGo() {
    window.location = document.getElementById("genredropdown").value;
}

function getArtistData(artistid,wrappernumber) {
        $.getJSON("includes/artistdata.php?action=getArtist", {artist:artistid}, function( data ) {
              if (data == 'STOP THAT!'){alert("stop that!");}
            else {
                
                $('#bandblock' + artistid).append('<div class="slided" id="slided'+artistid+'" style="display:none;"></div>');
                
                $("#slided"+artistid).html(
                        "<span class='spotifyUri'> <a id='spotifyUriA' href='" + data.spotify_uri + "'>Spotify App</a></span>" +
                        "<span class='spotifyUri'> <a id='spotifyUriB' href='" + data.spotify_web + "'>Spotify Web</a></span>" +
                        "<span class='ytTitleMobile'><a href='http://www.youtube.com/v/" + data.youtube_id + "'>Top Song (according to Last.fm): " + data.lastfm_topsong  + "</a></span>" +
                        "<span class='bc_url'> <a id='bc_url_id' href='" + data.bandcamp_url + "'>Bandcamp URL</a></span>" +
                        "<span class='bc_offsite'> <a id='bc_offsite_id' href='" + data.bandcamp_offsite + "'>Website</a></span>"
                );
                // $('#item-' + artistid).delay(1000).addClass('slideout');
                setTimeout(function(){
                    $('#item-' + artistid).addClass('slideout');
                    $(".slided").fadeIn("fast");
                },250);
                expandYoutube();
            }
        });
}


function expandYoutube(){
    $('.ytEmbed').hover(
        function(){ 
            $(this).animate({
                width: '200px'
            }, 400 );
        },
        function(){ 
            var $self = $(this);
            hoverTimeout = setTimeout(function() {
                $self.animate({
                    width: '40px'
                }, 400 );
            }, 1000);
        }
    );
}