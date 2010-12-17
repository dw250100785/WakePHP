/* http://code.google.com/p/jquery-easypassgen/
   Easy Password Generator for jQuery v1.4.
   Written by Yuri Pikin (me{at}jurius.name) December 2010.
   Licensed under the MIT (http://dev.jquery.com/browser/trunk/jquery/MIT-LICENSE.txt) license.
   Please feel free to use it.

   Example page enable at http://pasw.pm-tools.ru
*/

(function( $ ){

    var methods = {
        init:   function ( options ){

            return this.each(function( ){

                var $this = $(this);

                $this.html($.fn.easypassgen('generate', options ));
            });
        },
        generate: function( settings ){

            var system = {
                commonChars:    "bcdfghkmnprstvz",
                allConsonants:  "bcdfghkmnprstvzjqwx",
                allVowels:      "aeuy",
                allChars:       "bcdfghkmnprstvzjqwxaeuy",
                specialChars:   "#^:_-",
                randomize:      function( from, to){
                    from = typeof(from) != 'undefined' ? from : 0;
                    to = typeof(to) != 'undefined' ? to : from + 1;	
                    return Math.round(from + Math.random()*(to - from));
                },
                getRandomCharacter: function(a){
                    return a.charAt(this.randomize(0,a.length-1));
                }
            };

            var defaultSettings = {
                'syllables':        3,
                'numbers':          true,
                'specialchars':     false
            };

            if (settings){
                $.extend( defaultSettings, settings );
            }

            var numberProbability = 0, numberProbabilityStep = 0.25;
            var specialProbability = 0, specialProbabilityStep = 0.5;

            var generatedPass = '';

            for(var i = 0; i < defaultSettings.syllables; ++i) {
                    if(Math.round(Math.random())) {
                            generatedPass += system.getRandomCharacter(system.commonChars).toUpperCase() +
                                                                    system.getRandomCharacter(system.allVowels) +
                                                                    system.getRandomCharacter(system.allChars);
                    } else {
                            generatedPass += system.getRandomCharacter(system.allVowels).toUpperCase() +
                                                                    system.getRandomCharacter(system.commonChars);
                    }
                    if(defaultSettings.numbers && Math.round(Math.random() + numberProbability) && ( i != (defaultSettings.syllables-1))) {
                            generatedPass += system.randomize(0,9);
                            numberProbability += numberProbabilityStep;
                    } else if (defaultSettings.specialchars && Math.round(Math.random() + specialProbability) && (i != (defaultSettings.syllables-1))){
                            generatedPass += system.getRandomCharacter(system.specialChars);
                            specialProbabilityStep += specialProbabilityStep;
                    }
            }

            return generatedPass;
        }
    }

    $.fn.easypassgen = function( method ) {

        if ( methods[method] ) {
            return methods[method].apply( this, Array.prototype.slice.call( arguments, 1 ));
        } else if ( typeof method === 'object' || ! method ) {
            return methods.init.apply( this, arguments );
        } else {
            $.error( 'Method ' +  method + ' does not exist on jQuery.easypassgen' );
        }
    };
    
})( jQuery );
