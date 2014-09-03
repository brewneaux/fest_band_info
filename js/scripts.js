$(document).on("touchstart click", '.band', function(){
	var thisid = this.id;
	var artistid = thisid.split("-")[1];
	var wrappernumber = $(this).attr("wrappernumber");
	getArtistData(artistid,wrappernumber);
});

$(document).on("touchstart click", function(event){
	if(!$(event.target).closest('.slideout').length) {
		$(".hidden").fadeOut("medium", function(){ this.remove();});
		$("#pagehider").fadeOut("medium", function(){ this.remove();});
	}
});

$( document ).on( 'keydown', function ( e ) {
    if ( e.keyCode === 27 ) { // ESC
        $(".hidden").fadeOut("medium", function(){ this.remove();});
		$("#pagehider").fadeOut("medium", function(){ this.remove();});
    }
});


// function getArtistData(artistid,wrappernumber) {
// 	$.get("includes/artistdata.php?action=getArtist", {artist:artistid}, function(data) {
// 		$('.container').append('<div class="hidden" style="display:none;" id="popup"></div>');
//  		$('.container').append('<div style"display:none" id="pagehider"></div>');
//  		$("#popup").fadeIn("fast");
//  		$("#popup").html(
//  			"<span class='popupBand'>" + data.band + "</span>" + 
//  			"<span class='spotifyUri'> <a id='spotifyUriA' href='" + data.spotify_uri + "'>Spotify App</a></span>" + 
//  			"<span class='spotifyUri'> <a id='spotifyUriB' href='" + data.spotify_web + "'>Spotify Web</a></span>" +
//  			"<span class='ytTitle'>Top Song (according to Last.fm): " + data.lastfm_topsong  + "</span>" +
//  			"<div class='ytEmbed' ><div class='ytEmbedOne'><iframe width='300' height='300' src='https://www.youtube.com/embed/" + data.youtube_id + "?rel=0'></iframe></div></div>"
//  		);	
// 	}
// 	,"json");
// }


function getArtistData(artistid,wrappernumber) {
        $.getJSON("includes/artistdata.php?action=getArtist", {artist:artistid}, function( data ) {
                $('.container').append('<div class="hidden" style="display:none;" id="popup"></div>');
                $('.container').append('<div style"display:none" id="pagehider"></div>');
                $("#popup").fadeIn("fast");
                $("#popup").html(
                        "<span class='popupBand'>" + data.band + "</span>" +
                        "<span class='spotifyUri'> <a id='spotifyUriA' href='" + data.spotify_uri + "'>Spotify App</a></span>" +
                        "<span class='spotifyUri'> <a id='spotifyUriB' href='" + data.spotify_web + "'>Spotify Web</a></span>" +
                        "<span class='ytTitle'>Top Song (according to Last.fm): " + data.lastfm_topsong  + "</span>" +
                        "<div class='ytEmbed' ><div class='ytEmbedOne'><iframe width='300' height='300' src='https://www.youtube.com/embed/" + data.youtube_id + "?rel=0'></iframe></div></div>"
                );
        });
}