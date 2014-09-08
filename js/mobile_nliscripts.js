$(document).on("touchstart click", '.band', function(){
    if(!$(this).hasClass('slideout') || !$(this).hasClass('slideout')) {    
        var thisid = this.id;
        var artistid = thisid.split("-")[1];
        var wrappernumber = $(this).attr("wrappernumber");
        $('#close' + artistid).delay(400).fadeIn();
        $('#wrong' + artistid).delay(400).fadeIn();
        $('#arrow' + artistid).fadeOut(800);
        getArtistData(artistid,wrappernumber);
        
    }
});

$(document).on("touchstart click", '.artistconflict', function() {  
    var artistid = $(this).attr('artistid');
    getArtistConflict(artistid);
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
                    expandYoutube();
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
                $('.fa:hidden').fadeIn();
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
    $('#bandblock' + artistid).append('<div class="slided" id="slided'+artistid+'" style="display:none;"></div>');
    $('#slided' + artistid).load("includes/artistdata.php?action=getArtistHTML&artist=" + artistid);
     setTimeout(function(){
    $('#item-' + artistid).addClass('slideout');
    $(".slided").fadeIn("fast");
        },250);
    expandYoutube();
}

function getArtistConflict(artistid) {
    $('body').append('<div id="conflict_popup" style="display:none;"></div>');
    $('#conflict_popup').load("includes/artistdata.php?action=getConflicts&artist=" + artistid);

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

