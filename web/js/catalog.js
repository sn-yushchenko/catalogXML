$(document).ready(function(){
         
    $('#catalog-button').click(function(){
         $('#catalog').slideToggle("slow");       
    });
     $('li').click(function(){
         
                 $(this).children("ul").slideToggle("slow");
                  event.stopPropagation();
                        });
})