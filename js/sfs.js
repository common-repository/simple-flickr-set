jQuery(document).ready(function($){

$('.flickr-img').hide().eq(0).show();

var pause = 4000;
var motion = 200;

var imgs= $('.flickr-img');
var count = imgs.length;
var i = 0;

setTimeout(transition,pause);

function transition(){
    imgs.eq(i).hide();

    if(++i >= count){
        i = 0;
    }
    imgs.eq(i).animate({opacity:'toggle'}, motion);
    setTimeout(transition, pause);
}

});
