
//MULTILANGIAGE DETECTION
(function ($) {
    $.fn.smltown_text = function (text) {
        this.text(check(text));
    };
    $.fn.smltown_append = function (text) {
        this.append(check(text));
    };
    $.fn.smltown_prepend = function (text) {
        this.prepend(check(text));
    };
    function check(text) {
        if (text && !isNumber(text)) {
            return SMLTOWN.Message.translate(text);
        }
        return text;
    }
    function isNumber(n) {
        return !isNaN(parseFloat(n)) && isFinite(n);
    }
})(jQuery);

SMLTOWN.Message = {
    login: function (log) {
        if (typeof log == "object") {
            log = log.log; //server side
        }
        $("#smltown_login").remove(); //clean
        $("#smltown_body").append("<div class='smltown_dialog'><form id='smltown_login'>"
                + "<input type='text' class='smltown_name' placeholder='set your name'>"
                + "<input type='submit' value='Ok'>"
                + "<div class='smltown_button smltown_cancel'>Cancel</div>"
                + "<div class='smltown_log'></div>"
                + "</form><div>");
        if (log) {
            $("#smltown_login .smltown_log").html(log);
        }
        $("#smltown_login .smltown_name").focus();

        //LOGIN EVENTS
        $("#smltown_login").submit(function () { //submit 4 device buttons
            var name = $(this).find(".smltown_name").val();
            if (!name || !/\S/.test(name)) { //not only whitespaces
                $("#smltown_login .smltown_log").smltown_text("empty name!");
                return false;
            }

            for (var id in SMLTOWN.players) {
                if (SMLTOWN.players[id].name == name) {
                    $("#smltown_login .smltown_log").smltown_text("duplicatedName");
                    return false;
                }
            }

            SMLTOWN.Server.request.setName(name);

            $(".smltown_dialog").remove();
            return false; //prevent submit
        });
        $("#smltown_login .smltown_cancel").on("tap", function () {
            console.log("login cancel");
            if (typeof SMLTOWN.user != "undefined") {
                SMLTOWN.Server.request.deletePlayer(SMLTOWN.user.id, function () {
                    SMLTOWN.Load.showPage("gameList");
                });
            } else {
                SMLTOWN.Load.showPage("gameList");
            }
            $(".smltown_dialog").remove();
        });
    }
    ,
    setMessage: function (data) { //PERMANENT MESSAGES
        var t = this.translate;
        var text;

        var textArray = data.split(":");
        var action = data;
        if (textArray.length > 1) {
            action = textArray.shift();
            if (this[action]) {
                text = this[action](textArray.join(":"));
            } else {
                text = textArray.join(":");
            }
        } else {
            text = t(action);
        }

        clearTimeout(SMLTOWN.Action.wakeUpTimeout); //prevent asyncronic wakeup's after
        $("#smltown_filter").removeClass("smltown_sleep");
        $("#smltown_filter").addClass("smltown_message");

        this.showMessage(text, action);
    }
    ,
    showMessage: function (text, action) { //overrided
//        var $this = this;
//        var time = 0;
//        var stop = false;
//
//        clearTimeout(SMLTOWN.Action.wakeUpTimeout); //prevent asyncronic wakeup's after
//        $("#smltown_filter").removeClass("smltown_sleep");
//
//        setTimeout(function () {
//            $this.notify(text, function () {
//                if (SMLTOWN.user.status > -1 && SMLTOWN.Game.info.status == 1) {
//                    SMLTOWN.Action.sleep();
//                }
//                SMLTOWN.Action.cleanVotes();
//                SMLTOWN.Server.request.messageReceived(stop);
//            }, false);
//        }, time);
    }
    ,
    notify: function (text, okCallback, cancelCallback, gameId, important) {
        //console.log(gameId);
        if (gameId && SMLTOWN.Game.info.id != gameId) {
            this.external(text, gameId);
            return;
        }

        var $this = this;
        $("#smltown_popupOk").off("tap");
        $("#smltown_popupCancel").off("tap");

        if (text === "") { //===, not false
            console.log("empty text");
            //$this.removeNotification();
            return;
        }
        if (false === text) {
            okCallback();
        }

        console.log("text = " + text)

        $("#smltown_popupText").html(text);
        //show
        $("#smltown_filter").addClass("smltown_notification");
        $("#smltown_popupOk, #smltown_popupCancel").hide();

        if (okCallback) { //!= false
            $("#smltown_popupOk").show();
            $("#smltown_popupOk").one("tap", function (e) {
                e.preventDefault(); //prevent player select
                //hide
                $this.removeNotification(true);
                if (typeof okCallback == "function") {
                    clearTimeout(SMLTOWN.temp.wakeUpInterval);
                    okCallback();
                }
            });
        }

        if (cancelCallback) { //!= false
            $("#smltown_popupCancel").show();
            $("#smltown_popupCancel").one("tap", function (e) {
                e.preventDefault(); //prevent player select
                //hide
                $this.removeNotification(true);
                if (typeof cancelCallback == "function") {
                    cancelCallback();
                }
            });
        }
    }
    ,
    removeNotification: function (force) {
        var filter = $("#smltown_filter");
        if (!force && filter.hasClass("smltown_message")) {
            return;
        }
        filter.removeClass("smltown_notification");
    }
    ,
    setLog: function (text, type) {
        var log = $("#smltown_consoleText > div > div");
        var div = $("<div>");
        if (SMLTOWN.isNight) {
            div.addClass("smltown_night");
        }
        if (type) {
            div.addClass(type);
        }
        div.html(text);
        log.append(div);
        SMLTOWN.Server.loaded(); //end any loading screen
        //scroll
        //this.chatUpdate();
    }
//    ,
//    showNightLog: function(text, clean) {
//        if (clean) {
//            $("#smltown_console .text").html("");
//        }
//        $("#smltown_console .text").prepend("<div><span class='time'>" + new Date().toLocaleTimeString() + " </span>" + text + "</div>");
//    }
    ,
    flash: function (text, gameId) {

        if (gameId && SMLTOWN.Game.info.id != gameId) {
            this.external(text, gameId);
            return;
        }

        $("#smltown_flash").remove();
        var div = $("<div id='smltown_flash'><div>" + this.translate(text) + "</div></div>");
        $("#smltown_html").append(div);
        setTimeout(function () {
            div.remove();
        }, 1500);

        if (text == "adminRole") {
            SMLTOWN.Load.reloadGame();
        }
    }
    ,
    addChats: function () {
        var chatName = "chat" + SMLTOWN.Game.info.id;
        var chats = localStorage.getItem(chatName);
        if (!chats) {
            return;
        }

        var arrayChats = chats.split("·");
        var values;
        for (var i = 0; i < arrayChats.length; i++) {
            values = arrayChats[i].split("~");
            this.writeChat(values[0], values[1]);
        }
        SMLTOWN.Transform.chatUpdate();
        SMLTOWN.Add.userNamesByClass();
    }
    ,
    addChat: function (text, playId, gameId, name) { //from server
        if (typeof playId == "undefined") {
            playId = null;
        }

        var chatName = "chat" + SMLTOWN.Game.info.id;
        var chat = localStorage.getItem(chatName);
        if (!chat) {
            chat = "";
        }
        localStorage.setItem(chatName, chat + "·" + text + "~" + playId);

        if (gameId && SMLTOWN.Game.info.id != gameId) {
            this.external(text, gameId, name);
            return;
        }

        this.writeChat(text, playId);
    }
    ,
    writeChat: function (text, playId) {
        var name = "";
        if (typeof SMLTOWN.players[playId] != "undefined") { //if player no longer exists
            name = SMLTOWN.players[playId].name + ": ";
        }
        $("#smltown_consoleText > div > div").append("<div><span class='id" + playId + "'>" + name + "</span>" + text + "</div>");
    }
    ,
    clearChat: function () {
        localStorage.removeItem("chat" + SMLTOWN.Game.info.id);
//        localStorage.clear();
    }
    ,
    translate: function (some, attr) {
        if (!lang[some]) {
//            if (attr) {
//                return attr + " " + some;
//            }
            console.log("not translation: " + some);
            return some;
        }
        return lang[some];
    }
    ,
    external: function (text, gameId, name) {

        text = "<small>" + name + ": </small> " + this.translate(text);

        $("#smltown_external").remove();
        var div = $("<div id='smltown_external'>" + text + "</div>");
        div.click(function () {
            window.location.hash = "game?" + gameId;
        });
        $("#smltown_html").append(div);

        setTimeout(function () {
            $("#smltown_external").addClass("smltown_visible");
        }, 100);

        setTimeout(function () {
            $("#smltown_external").removeClass("smltown_visible");
        }, 4000);
    }
};
