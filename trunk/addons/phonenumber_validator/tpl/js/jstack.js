/*!
 * jStack - jQuery Event Stack Management
 * by Eric Garside (http://eric.garside.name)
 * 
 * Dual licensed under:
 * 	MIT: http://www.opensource.org/licenses/mit-license.php
 *	GPLv3: http://www.opensource.org/licenses/gpl-3.0.html
 */
(function($){
 
	$.extend($.fn, {
		// Determine the objects position in the cache, if any
		cachePosition: function(){
			return $.data(this[0]);
		},
 
		// Allow the user to splice an event into a specific position in the event cache 
		bindIntoStack: function(pos, type, func){
			return this.each(function(){
				var namespaces = type.split("."),	// Explode out namespaces, if any
					evType = namespaces.shift(),	// Grab the actual type
					el = $(this),					// Use the jQuery reference to the first element instead of the domElement
					position = el.cachePosition();	// Get the location of the element in the cache.
 
				if (!position) return;				// If we have a position, we can access the cache, which holds the jQuery event stack.
 
				var cache = $.cache[ position ];	// Grab a reference to the cache so we can do some sanity checks
 
				el.bind(type, func);				// Now we need to bind the new function to the call stack through jQuery
 
				if (!cache ||						// If we don't have the a pre-existing event cache, bind the function as a new entry and exit
					!cache.events || 
					!cache.events[ evType ]) return;	
 
				var events = cache.events[ evType ],// Grab a copy of the events cache for the given type
					fromExpando = [],				// [stackPosition => uuidIDInjQueryCache, ...]
					toReplace = null,				// A copy of the function we'll be replacing, if any
					i = 0;
 
				$.each(events, function(k){
					fromExpando.push(k);
					if (i == pos) toReplace = this;	// If the positions are equal, this is the function we want to replace
					++i;
				})
 
				if (!toReplace) return;		 		// If the position in the stack has not yet been reached, there's no slicing to be done
 
				var newPos = fromExpando.pop();		// The position we'll be placing in
 
				// Perform the actual position swap in the cache
				$.cache[ position ].events[ evType ][ fromExpando[pos] ] = $.cache[ position ].events[ evType ][ newPos ];
				$.cache[ position ].events[ evType ][ newPos ] = toReplace;
			})
 
			return this;
		}
	});
 
})(jQuery);
