/**
* BAClickToReveal question javascript
*
* @copyright  Bright Alley Knowledge and Learning
* @author     Mannes Brak
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
var activeone = false;
$(document).ready(function(){
        canvas2 = document.createElement('canvas');

        canvas2.setAttribute('id', 'c2');
        $('#canvasDiv').append(canvas2);

        if (typeof G_vmlCanvasManager != 'undefined') {
            canvas2 = G_vmlCanvasManager.initElement(canvas2);
        }




        hsimage.onload = function(){
            $("#ubhotspotsimage"+hsufix).css('width',hsimage.width).css('height',hsimage.height);
            loadAreaResponses();

        }

        // IE hack with cached images
        if(hsimage.width){
            $("#ubhotspotsimage"+hsufix).css('width',hsimage.width).css('height',hsimage.height);
            if(!areaLoaded){
                loadAreaResponses();
            }
        }



        function  loadAreaResponses(){

            areaLoaded = true;
            canvas2.setAttribute('width', hsimage.width);
            $('#canvasDiv').css('width', hsimage.width + 'px');
            canvas2.setAttribute('height', hsimage.height);
            $('#c2').css("position", "absolute").css("top", 0).css("left", 0);

            if($('#c2 div').length){
                $('#c2 div').css('width', hsimage.width + 'px');
                $('#c2 div').css('height', hsimage.height + 'px');
            }


            function findHoverHotspot(x, y) {
                var hit = false;

                $.each(hotspots, function(index, hs) {
                    if (hs.shape == "rect") {
                        if (x >= hs.startX && x <= hs.endX && y >= hs.startY && y <= hs.endY) {
                            hit = index;
                        }
                    } else if (hs.shape == "ellip") {
                        var w = hs.endX - hs.startX;
                        var h = hs.endY - hs.startY;

                        // Ellipse radius
                        var rx = w / 2;
                        var ry = h / 2;

                        // Ellipse center
                        var cx = hs.startX + rx;
                        var cy = hs.startY + ry;

                        var dx = (x - cx) / rx;
                        var dy = (y - cy) / ry;
                        var distance = dx * dx + dy * dy;

                        if (distance < 1.0){
                            hit = index;
                        }
                    }
                });
                if (hit !== false && highLightHotspots) {
                    highlightHotspot(hit);
                }
                if (hit !== false && clickOnHover) {
                    $('#c2').click();
                }
                return hit;
            }

            function displayText(text, target) {
                target.html(text);
                if (text.length && scrollToResult) $('html,body').animate({ scrollTop: target.offset().top}, 'slow');
            }

            function highlightHotspot(hotspotIndex, clear) {
                if (clear == null) clear = true;
                if (clear) canvas2.width = canvas2.width; // Clears the canvas

                if (hotspotIndex !== null) {
                var ctx = canvas2.getContext("2d");
                var hs = hotspots[hotspotIndex];
                if (hs.shape == "rect") {
                    ctx.strokeStyle = highlightColor;
                    ctx.lineJoin = "round";
                    ctx.lineWidth = 2;

                    ctx.strokeRect(hs.startX, hs.startY, hs.endX - hs.startX, hs.endY - hs.startY);

                } else if (hs.shape == "ellip") {
                    ctx.strokeStyle = highlightColor;
                    ctx.lineJoin = "round";
                    ctx.lineWidth = 2;

                    ctx.beginPath();

                    // startX, startY
                    ctx.moveTo(hs.cx, hs.startY);
                    hs.rx = Math.abs(hs.rx);
                    hs.ry = Math.abs(hs.ry);
                    // Control points: cp1x, cp1y, cp2x, cp2y, destx, desty
                    // go clockwise: top-middle, right-middle, bottom-middle, then left-middle
                    ctx.bezierCurveTo(hs.cx + hs.rx, hs.startY, hs.endX, hs.cy - hs.ry, hs.endX, hs.cy);
                    ctx.bezierCurveTo(hs.endX, hs.cy + hs.ry, hs.cx + hs.rx, hs.endY, hs.cx, hs.endY);
                    ctx.bezierCurveTo(hs.cx - hs.rx, hs.endY, hs.startX, hs.cy + hs.ry, hs.startX, hs.cy);
                    ctx.bezierCurveTo(hs.startX, hs.cy - hs.ry, hs.cx - hs.rx, hs.startY, hs.cx, hs.startY);

                    ctx.stroke();
                    ctx.closePath();

                }
                }
                if (activeone !== false && clear == true) highlightHotspot(activeone, false);
            }

            $('#c2').mousemove( function(event) {
                var offset_t = $(this).offset().top - $(window).scrollTop();
                var offset_l = $(this).offset().left - $(window).scrollLeft();

                var left = Math.round( (event.clientX - offset_l) );
                var top = Math.round( (event.clientY - offset_t) );

                var i = findHoverHotspot(left, top);
                if (i !== false) {
                    $(this).css('cursor', 'pointer');
                } else {
                    $(this).css('cursor', 'default');
                    if (highLightHotspots) highlightHotspot(null);
                }
            });
            $('#c2').click( function(event) {
                var offset_t = $(this).offset().top - $(window).scrollTop();
                var offset_l = $(this).offset().left - $(window).scrollLeft();

                var left = Math.round( (event.clientX - offset_l) );
                var top = Math.round( (event.clientY - offset_t) );

                var i = findHoverHotspot(left, top);
                activeone = i;
                if (i !== false) {
                    displayText(texts[i], $('.feedback p'));
		      highlightHotspot(i);
                } else {
                    displayText('', $('.feedback p'));
                    highlightHotspot(null);
                }
            });

        }

    }
);