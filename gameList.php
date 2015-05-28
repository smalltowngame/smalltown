<?php
session_start();
$_SESSION['gameId'] = null;
?>

<!--Local game List mode only-->

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>  
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
        <title>Small Town</title>
    </head>

    <body>

        <div id="smltown_content">
            <div class="smltown_title">
                Running Games:
            </div>

            <div id="smltown_games"></div>
            <div id="smltown_loadingDiv"></div>

            <div id="smltown_footer">
                <br/>                
            </div>
            <div id="smltown_log" class="title" style="color:red"></div>
        </div>

        <button id="smltown_reload" onclick="reload()">reload</button>

        <script>

//            window.onunload = function() {
//                stopLocalGameRequests();
//            };
            window.onbeforeunload = function() {
                console.log("stopLocalGameRequests")
                stopLocalGameRequests();
                return null;
            };

            var XMLHttpRequestTimeout = 500;
            var localGameRequests = {}; //before jquery load!!

            //load jquery if not loaded
            if (typeof jQuery == 'undefined') {
                var headTag = document.getElementsByTagName("head")[0];
                var jqTag = document.createElement('script');
                jqTag.type = 'text/javascript';
                jqTag.src = 'libs/jquery-1.11.0.min.js';
                jqTag.onload = ready;
                headTag.appendChild(jqTag);
            } else {
                ready();
            }

            function reload() {
                stopLocalGameRequests();
                XMLHttpRequestTimeout += 500000 / XMLHttpRequestTimeout;
                console.log("tiemout: " + XMLHttpRequestTimeout);
                updateGames();
                ready();
            }

            function ready() {
                $("#smltown_games").html("");
                loadingGames(); //load visuals
//                $("#content").append(document.URL);

//            setTimeout(function() {
//            ImgPing["localhost"].img.removeAttr("src");
//            },2000);

                if ($(".smltown_game.smltown_local").length) {
                    XMLHttpPing("", "localhost");
                }
                for (var i = 1; i <= 255; i++) {
                    XMLHttpPing("192.168.1.", i);
                }
            }

            function XMLHttpPing(ipBase, i) {
                var gameReq = {};
                gameReq = new XMLHttpRequest();
                gameReq.ip = ipBase + i;
                gameReq.href = "http://" + gameReq.ip + ":8080/smalltown/";
                gameReq.open('HEAD', gameReq.href + "index.php", true); //async
                gameReq.timeout = XMLHttpRequestTimeout;
//                gameReq.ontimeout = function () {
//                    console.log("Timed out!!!");
//                }
                gameReq.send();
                gameReq.onreadystatechange = function() {
                    if (this.readyState == 4) {
                        if (this.status == 200) {
                            console.log(222)
                            this.smalltownHeader = this.getResponseHeader('smalltown');
                            var url = this.href;
                            if (isNaN(this.smalltownHeader)) {
                                //search localhost repeated games and refuse
                                if ("localhost" != i //not this one
                                        && localGameRequests["localhost"]
                                        && this.smalltownHeader == localGameRequests["localhost"].smalltownHeader) {
                                    console.log("game name: " + localGameRequests["localhost"].smalltownHeader + " repeated");
                                    return;
                                }
                                url += this.smalltownHeader + "/";
                            }
                            var nameHeader = this.getResponseHeader('name');
                            addLocalGamesRow(url, this.ip, nameHeader);
                        }

                        if (areXMLHttpRequestFinished()) {
                            loadedGames();
                        }
                    }
                };
                localGameRequests[i] = gameReq;
            }

//            function ImgPing(ipBase, i) {
//                var $this = this;
//                this.ip = ipBase + i;
//                this.href = "http://" + this.ip + ":8080/smalltown";
//                this.img = $("<img src = '" + this.href + "/ping'>");
//                this.img.one("load", function() {
//                    addLocalGamesRow($this.href, $this.ip, "local");
//                });
//            }

            function loadingGames() {
                $("#loadingDiv").addClass("ajax-loader");
            }
            function loadedGames() {
                $("#loadingDiv").removeClass("ajax-loader");
            }

            function areXMLHttpRequestFinished() {
                for (var key in localGameRequests) {
                    if (localGameRequests[key].readyState < 4) {
                        return false;
                    }
                }
                return true;
            }

            function stopLocalGameRequests() {
                for (var key in localGameRequests) {
                    if (localGameRequests[key].abort) {
                        localGameRequests[key].abort();
                        //console.log("abort");
                    }
                }
            }

            function addLocalGamesRow(href, ip, name) {
                console.log(111)
                var div = $("<div class='smltown_game local'>");
                div.append("<span class='name'>Local Game</span>");
                div.append("<span class='smltown_admin'><small>ip: " + ip + "</small>" + name + "</span>");

                $("#smltown_games").prepend(div);
                div.click(function() {
                    stopLocalGameRequests();
                    //window.location.href = href + "game.php";
                    $("#smltown_html").load(Game.path + "game.php");
                });
            }

        </script>

    </body>
</html>
