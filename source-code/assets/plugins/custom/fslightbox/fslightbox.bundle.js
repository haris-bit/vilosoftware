! function(e, t) {
    if ("object" == typeof exports && "object" == typeof module) module.exports = t();
    else if ("function" == typeof define && define.amd) define([], t);
    else {
        var n = t();
        for (var o in n)("object" == typeof exports ? exports : e)[o] = n[o]
    }
}(window, (function() {
    return function(e) {
        var t = {};

        function n(o) {
            if (t[o]) return t[o].exports;
            var i = t[o] = {
                i: o,
                l: !1,
                exports: {}
            };
            return e[o].call(i.exports, i, i.exports, n), i.l = !0, i.exports
        }
        return n.m = e, n.c = t, n.d = function(e, t, o) {
            n.o(e, t) || Object.defineProperty(e, t, {
                enumerable: !0,
                get: o
            })
        }, n.r = function(e) {
            "undefined" != typeof Symbol && Symbol.toStringTag && Object.defineProperty(e, Symbol.toStringTag, {
                value: "Module"
            }), Object.defineProperty(e, "__esModule", {
                value: !0
            })
        }, n.t = function(e, t) {
            if (1 & t && (e = n(e)), 8 & t) return e;
            if (4 & t && "object" == typeof e && e && e.__esModule) return e;
            var o = Object.create(null);
            if (n.r(o), Object.defineProperty(o, "default", {
                    enumerable: !0,
                    value: e
                }), 2 & t && "string" != typeof e)
                for (var i in e) n.d(o, i, function(t) {
                    return e[t]
                }.bind(null, i));
            return o
        }, n.n = function(e) {
            var t = e && e.__esModule ? function() {
                return e.default
            } : function() {
                return e
            };
            return n.d(t, "a", t), t
        }, n.o = function(e, t) {
            return Object.prototype.hasOwnProperty.call(e, t)
        }, n.p = "", n(n.s = 0)
    }([function(e, t, n) {
        "use strict";
        n.r(t);
        var o, i = "fslightbox-",
            r = "".concat(i, "styles"),
            s = "".concat(i, "cursor-grabbing"),
            a = "".concat(i, "full-dimension"),
            c = "".concat(i, "flex-centered"),
            l = "".concat(i, "open"),
            u = "".concat(i, "transform-transition"),
            d = "".concat(i, "absoluted"),
            p = "".concat(i, "slide-btn"),
            f = "".concat(p, "-container"),
            h = "".concat(i, "fade-in"),
            m = "".concat(i, "fade-out"),
            g = h + "-strong",
            v = m + "-strong",
            b = "".concat(i, "opacity-"),
            x = "".concat(b, "1"),
            y = "".concat(i, "source");

        function S(e) {
            return (S = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function(e) {
                return typeof e
            } : function(e) {
                return e && "function" == typeof Symbol && e.constructor === Symbol && e !== Symbol.prototype ? "symbol" : typeof e
            })(e)
        }

        function w(e) {
            var t, n = e.props,
                o = 0,
                i = {};
            this.getSourceTypeFromLocalStorageByUrl = function(e) {
                return t[e] ? t[e] : r(e)
            }, this.handleReceivedSourceTypeForUrl = function(e, n) {
                !1 === i[n] && (o--, "invalid" !== e ? i[n] = e : delete i[n], 0 === o && (function(e, t) {
                    for (var n in t) e[n] = t[n]
                }(t, i), localStorage.setItem("fslightbox-types", JSON.stringify(t))))
            };
            var r = function(e) {
                o++, i[e] = !1
            };
            n.disableLocalStorage ? (this.getSourceTypeFromLocalStorageByUrl = function() {}, this.handleReceivedSourceTypeForUrl = function() {}) : (t = JSON.parse(localStorage.getItem("fslightbox-types"))) || (t = {}, this.getSourceTypeFromLocalStorageByUrl = r)
        }

        function L(e, t, n, o) {
            var i = e.data,
                r = e.elements.sources,
                s = n / o,
                a = 0;
            this.adjustSize = function() {
                if ((a = i.maxSourceWidth / s) < i.maxSourceHeight) return n < i.maxSourceWidth && (a = o), c();
                a = o > i.maxSourceHeight ? i.maxSourceHeight : o, c()
            };
            var c = function() {
                r[t].style.width = a * s + "px", r[t].style.height = a + "px"
            }
        }

        function C(e, t) {
            var n = this,
                o = e.collections.sourceSizers,
                i = e.elements,
                r = i.sourceAnimationWrappers,
                s = i.sourceMainWrappers,
                a = i.sources,
                c = e.resolve;

            function l(e, n) {
                o[t] = c(L, [t, e, n]), o[t].adjustSize()
            }
            this.runActions = function(e, o) {
                a[t].classList.add(x), r[t].classList.add(g), s[t].removeChild(s[t].firstChild), l(e, o), n.runActions = l
            }
        }

        function F(e, t) {
            var n, o = this,
                i = e.elements.sources,
                r = e.props,
                s = (0, e.resolve)(C, [t]);
            this.handleImageLoad = function(e) {
                var t = e.target,
                    n = t.naturalWidth,
                    o = t.naturalHeight;
                s.runActions(n, o)
            }, this.handleVideoLoad = function(e) {
                var t = e.target,
                    o = t.videoWidth,
                    i = t.videoHeight;
                n = !0, s.runActions(o, i)
            }, this.handleNotMetaDatedVideoLoad = function() {
                n || o.handleYoutubeLoad()
            }, this.handleYoutubeLoad = function() {
                var e = 1920,
                    t = 1080;
                r.maxYoutubeDimensions && (e = r.maxYoutubeDimensions.width, t = r.maxYoutubeDimensions.height), s.runActions(e, t)
            }, this.handleCustomLoad = function() {
                setTimeout((function() {
                    var e = i[t];
                    s.runActions(e.offsetWidth, e.offsetHeight)
                }))
            }
        }

        function A(e, t, n) {
            var o = e.elements.sources,
                i = e.props.customClasses,
                r = i[t] ? i[t] : "";
            o[t].className = n + " " + r
        }

        function E(e, t) {
            var n = e.elements.sources,
                o = e.props.customAttributes;
            for (var i in o[t]) n[t].setAttribute(i, o[t][i])
        }

        function I(e, t) {
            var n = e.collections.sourceLoadHandlers,
                o = e.elements,
                i = o.sources,
                r = o.sourceAnimationWrappers,
                s = e.props.sources;
            i[t] = document.createElement("img"), A(e, t, y), i[t].src = s[t], i[t].onload = n[t].handleImageLoad, E(e, t), r[t].appendChild(i[t])
        }

        function T(e, t) {
            var n = e.collections.sourceLoadHandlers,
                o = e.elements,
                i = o.sources,
                r = o.sourceAnimationWrappers,
                s = e.props,
                a = s.sources,
                c = s.videosPosters;
            i[t] = document.createElement("video"), A(e, t, y), i[t].src = a[t], i[t].onloadedmetadata = function(e) {
                n[t].handleVideoLoad(e)
            }, i[t].controls = !0, E(e, t), c[t] && (i[t].poster = c[t]);
            var l = document.createElement("source");
            l.src = a[t], i[t].appendChild(l), setTimeout(n[t].handleNotMetaDatedVideoLoad, 3e3), r[t].appendChild(i[t])
        }

        function W(e, t) {
            var n = e.collections.sourceLoadHandlers,
                o = e.elements,
                r = o.sources,
                s = o.sourceAnimationWrappers,
                a = e.props.sources;
            r[t] = document.createElement("iframe"), A(e, t, "".concat(y, " ").concat(i, "youtube-iframe")), r[t].src = "https://www.youtube.com/embed/".concat(a[t].match(/^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/)[2]), r[t].allowFullscreen = !0, E(e, t), s[t].appendChild(r[t]), n[t].handleYoutubeLoad()
        }

        function N(e, t) {
            var n = e.collections.sourceLoadHandlers,
                o = e.elements,
                i = o.sources,
                r = o.sourceAnimationWrappers,
                s = e.props.sources;
            i[t] = s[t], A(e, t, "".concat(i[t].className, " ").concat(y)), r[t].appendChild(i[t]), n[t].handleCustomLoad()
        }

        function z(e, t) {
            var n = e.elements,
                o = n.sources,
                r = n.sourceAnimationWrappers,
                s = n.sourceMainWrappers;
            e.props.sources, o[t] = document.createElement("div"), o[t].className = "".concat(i, "invalid-file-wrapper ").concat(c), o[t].innerHTML = "Invalid source", r[t].classList.add(g), r[t].appendChild(o[t]), s[t].removeChild(s[t].firstChild)
        }

        function M(e) {
            var t = e.collections,
                n = t.sourceLoadHandlers,
                o = t.sourcesRenderFunctions,
                i = e.core.sourceDisplayFacade,
                r = e.resolve;
            this.runActionsForSourceTypeAndIndex = function(t, s) {
                var a;
                switch ("invalid" !== t && (n[s] = r(F, [s])), t) {
                    case "image":
                        a = I;
                        break;
                    case "video":
                        a = T;
                        break;
                    case "youtube":
                        a = W;
                        break;
                    case "custom":
                        a = N;
                        break;
                    default:
                        a = z
                }
                o[s] = function() {
                    return a(e, s)
                }, i.displaySourcesWhichShouldBeDisplayed()
            }
        }

        function H() {
            var e, t, n, o = function(e) {
                    var t = document.createElement("a");
                    return t.href = e, "www.youtube.com" === t.hostname
                },
                i = function(e) {
                    return e.slice(0, e.indexOf("/"))
                };

            function r() {
                if (4 !== n.readyState) {
                    if (2 === n.readyState) {
                        var e;
                        switch (i(n.getResponseHeader("content-type"))) {
                            case "image":
                                e = "image";
                                break;
                            case "video":
                                e = "video";
                                break;
                            default:
                                e = "invalid"
                        }
                        n.onreadystatechange = null, n.abort(), t(e)
                    }
                } else t("invalid")
            }
            this.setUrlToCheck = function(t) {
                e = t
            }, this.getSourceType = function(i) {
                if (o(e)) return i("youtube");
                t = i, (n = new XMLHttpRequest).onreadystatechange = r, n.open("GET", e, !0), n.send()
            }
        }

        function k(e, t, n) {
            var o = e.props,
                i = o.types,
                r = o.type,
                s = o.sources,
                a = e.resolve;
            this.getTypeSetByClientForIndex = function(e) {
                var t;
                return i && i[e] ? t = i[e] : r && (t = r), t
            }, this.retrieveTypeWithXhrForIndex = function(e) {
                var o = a(H);
                o.setUrlToCheck(s[e]), o.getSourceType((function(o) {
                    t.handleReceivedSourceTypeForUrl(o, s[e]), n.runActionsForSourceTypeAndIndex(o, e)
                }))
            }
        }

        function D(e, t) {
            var n = e.componentsServices.hideSourceLoaderIfNotYetCollection,
                o = e.elements,
                i = o.sourceWrappersContainer,
                r = o.sourceMainWrappers;
            r[t] = document.createElement("div"), r[t].className = "".concat(d, " ").concat(a, " ").concat(c), r[t].innerHTML = '<div class="fslightbox-loader"><div></div><div></div><div></div><div></div></div>';
            var s = r[t].firstChild;
            n[t] = function() {
                    r[t].contains(s) && r[t].removeChild(s)
                }, i.appendChild(r[t]),
                function(e, t) {
                    var n = e.elements,
                        o = n.sourceMainWrappers,
                        i = n.sourceAnimationWrappers;
                    i[t] = document.createElement("div"), o[t].appendChild(i[t])
                }(e, t)
        }

        function O(e, t, n, o) {
            var r = document.createElementNS("http://www.w3.org/2000/svg", "svg");
            r.setAttributeNS(null, "width", t), r.setAttributeNS(null, "height", t), r.setAttributeNS(null, "viewBox", n);
            var s = document.createElementNS("http://www.w3.org/2000/svg", "path");
            return s.setAttributeNS(null, "class", "".concat(i, "svg-path")), s.setAttributeNS(null, "d", o), r.appendChild(s), e.appendChild(r), r
        }

        function R(e, t) {
            var n = document.createElement("div");
            return n.className = "".concat(i, "toolbar-button ").concat(c), n.title = t, e.appendChild(n), n
        }

        function j(e) {
            var t = e.props.sources,
                n = e.elements.container,
                o = document.createElement("div");
            o.className = "".concat(i, "nav"), n.appendChild(o),
                function(e, t) {
                    var n = document.createElement("div");
                    n.className = "".concat(i, "toolbar"), t.appendChild(n),
                        function(e, t) {
                            var n = e.componentsServices,
                                o = e.core.fullscreenToggler,
                                i = e.data,
                                r = "M4.5 11H3v4h4v-1.5H4.5V11zM3 7h1.5V4.5H7V3H3v4zm10.5 6.5H11V15h4v-4h-1.5v2.5zM11 3v1.5h2.5V7H15V3h-4z",
                                s = R(t);
                            s.title = "Enter fullscreen";
                            var a = O(s, "20px", "0 0 18 18", r);
                            n.enterFullscreen = function() {
                                i.isFullscreenOpen = !0, s.title = "Exit fullscreen", a.setAttributeNS(null, "width", "24px"), a.setAttributeNS(null, "height", "24px"), a.setAttributeNS(null, "viewBox", "0 0 950 1024"), a.firstChild.setAttributeNS(null, "d", "M682 342h128v84h-212v-212h84v128zM598 810v-212h212v84h-128v128h-84zM342 342v-128h84v212h-212v-84h128zM214 682v-84h212v212h-84v-128h-128z")
                            }, n.exitFullscreen = function() {
                                i.isFullscreenOpen = !1, s.title = "Enter fullscreen", a.setAttributeNS(null, "width", "20px"), a.setAttributeNS(null, "height", "20px"), a.setAttributeNS(null, "viewBox", "0 0 18 18"), a.firstChild.setAttributeNS(null, "d", r)
                            }, s.onclick = function() {
                                i.isFullscreenOpen ? o.exitFullscreen() : o.enterFullscreen()
                            }
                        }(e, n),
                        function(e, t) {
                            var n = R(t, "Close");
                            n.onclick = e.core.lightboxCloser.closeLightbox, O(n, "20px", "0 0 24 24", "M 4.7070312 3.2929688 L 3.2929688 4.7070312 L 10.585938 12 L 3.2929688 19.292969 L 4.7070312 20.707031 L 12 13.414062 L 19.292969 20.707031 L 20.707031 19.292969 L 13.414062 12 L 20.707031 4.7070312 L 19.292969 3.2929688 L 12 10.585938 L 4.7070312 3.2929688 z")
                        }(e, n)
                }(e, o), t.length > 1 && function(e, t) {
                    var n = e.componentsServices,
                        o = e.props.sources,
                        r = (e.stageIndexes, document.createElement("div"));
                    r.className = "".concat(i, "slide-number-container");
                    var s = document.createElement("div");
                    s.className = c;
                    var a = document.createElement("span");
                    n.setSlideNumber = function(e) {
                        return a.innerHTML = e
                    };
                    var l = document.createElement("span");
                    l.className = "".concat(i, "slash");
                    var u = document.createElement("div");
                    u.innerHTML = o.length, r.appendChild(s), s.appendChild(a), s.appendChild(l), s.appendChild(u), t.appendChild(r), setTimeout((function() {
                        s.offsetWidth > 55 && (r.style.justifyContent = "flex-start")
                    }))
                }(e, o)
        }

        function P(e, t) {
            var n = this,
                o = e.elements.sourceMainWrappers,
                i = e.props,
                r = 0;
            this.byValue = function(e) {
                return r = e, n
            }, this.negative = function() {
                s(-a())
            }, this.zero = function() {
                s(0)
            }, this.positive = function() {
                s(a())
            };
            var s = function(e) {
                    o[t].style.transform = "translateX(".concat(e + r, "px)"), r = 0
                },
                a = function() {
                    return (1 + i.slideDistance) * innerWidth
                }
        }

        function X(e, t, n, o) {
            var i = e.elements.container,
                r = n.charAt(0).toUpperCase() + n.slice(1),
                s = document.createElement("div");
            s.className = "".concat(f, " ").concat(f, "-").concat(n), s.title = "".concat(r, " slide"), s.onclick = t,
                function(e, t) {
                    var n = document.createElement("div");
                    n.className = "".concat(p, " ").concat(c), O(n, "20px", "0 0 20 20", t), e.appendChild(n)
                }(s, o), i.appendChild(s)
        }

        function B(e, t) {
            var n = e.classList;
            n.contains(t) && n.remove(t)
        }

        function V(e) {
            var t = this,
                n = e.core,
                o = n.eventsDispatcher,
                i = n.fullscreenToggler,
                r = n.globalEventsController,
                s = n.scrollbarRecompensor,
                a = e.data,
                c = e.elements,
                u = e.props,
                d = e.slideSwipingProps;
            this.isLightboxFadingOut = !1, this.runActions = function() {
                t.isLightboxFadingOut = !0, c.container.classList.add(v), r.removeListeners(), u.exitFullscreenOnClose && a.isFullscreenOpen && i.exitFullscreen(), setTimeout((function() {
                    t.isLightboxFadingOut = !1, d.isSwiping = !1, c.container.classList.remove(v), document.documentElement.classList.remove(l), s.removeRecompense(), document.body.removeChild(c.container), o.dispatch("onClose")
                }), 220)
            }
        }

        function U(e) {
            return e.touches ? e.touches[0].clientX : e.clientX
        }

        function q(e) {
            var t = e.core,
                n = t.lightboxCloser,
                o = t.fullscreenToggler,
                i = t.slideChangeFacade;
            this.listener = function(e) {
                switch (e.key) {
                    case "Escape":
                        n.closeLightbox();
                        break;
                    case "ArrowLeft":
                        i.changeToPrevious();
                        break;
                    case "ArrowRight":
                        i.changeToNext();
                        break;
                    case "F11":
                        e.preventDefault(), o.enterFullscreen()
                }
            }
        }

        function _(e) {
            var t = e.collections.sourceMainWrappersTransformers,
                n = e.elements,
                o = e.slideSwipingProps,
                i = e.stageIndexes;
            this.runActionsForEvent = function(e) {
                var t, a, c;
                n.container.contains(n.slideSwipingHoverer) || n.container.appendChild(n.slideSwipingHoverer), t = n.container, a = s, (c = t.classList).contains(a) || c.add(a), o.swipedX = U(e) - o.downClientX, r(i.current, "zero"), void 0 !== i.previous && o.swipedX > 0 ? r(i.previous, "negative") : void 0 !== i.next && o.swipedX < 0 && r(i.next, "positive")
            };
            var r = function(e, n) {
                t[e].byValue(o.swipedX)[n]()
            }
        }

        function Y(e) {
            var t, n = e.props.sources,
                o = e.resolve,
                i = e.slideSwipingProps,
                r = o(_),
                s = (t = !1, function() {
                    return !t && (t = !0, requestAnimationFrame((function() {
                        t = !1
                    })), !0)
                });
            1 === n.length ? this.listener = function() {
                i.swipedX = 1
            } : this.listener = function(e) {
                i.isSwiping && s() && r.runActionsForEvent(e)
            }
        }

        function J(e) {
            var t = e.collections.sourceMainWrappersTransformers,
                n = e.core.slideIndexChanger,
                o = e.elements.sourceMainWrappers,
                i = e.stageIndexes;
            this.runPositiveSwipedXActions = function() {
                void 0 === i.previous || (r("positive"), n.changeTo(i.previous)), r("zero")
            }, this.runNegativeSwipedXActions = function() {
                void 0 === i.next || (r("negative"), n.changeTo(i.next)), r("zero")
            };
            var r = function(e) {
                o[i.current].classList.add(u), t[i.current][e]()
            }
        }

        function G(e, t) {
            e.contains(t) && e.removeChild(t)
        }

        function $(e) {
            var t = e.core.lightboxCloser,
                n = e.elements,
                o = e.resolve,
                i = e.slideSwipingProps,
                r = o(J);
            this.runNoSwipeActions = function() {
                G(n.container, n.slideSwipingHoverer), i.isSourceDownEventTarget || t.closeLightbox(), i.isSwiping = !1
            }, this.runActions = function() {
                i.swipedX > 0 ? r.runPositiveSwipedXActions() : r.runNegativeSwipedXActions(), G(n.container, n.slideSwipingHoverer), n.container.classList.remove(s), i.isSwiping = !1
            }
        }

        function K(e) {
            var t = e.resolve,
                n = e.slideSwipingProps,
                o = t($);
            this.listener = function() {
                n.isSwiping && (n.swipedX ? o.runActions() : o.runNoSwipeActions())
            }
        }

        function Q(e) {
            var t, n, o;
            n = (t = e).core.classFacade, o = t.elements, n.removeFromEachElementClassIfContains = function(e, t) {
                    for (var n = 0; n < o[e].length; n++) B(o[e][n], t)
                },
                function(e) {
                    var t = e.core.eventsDispatcher,
                        n = e.props;
                    t.dispatch = function(e) {
                        n[e] && n[e]()
                    }
                }(e),
                function(e) {
                    var t = e.componentsServices,
                        n = e.core.fullscreenToggler;
                    n.enterFullscreen = function() {
                        t.enterFullscreen();
                        var e = document.documentElement;
                        e.requestFullscreen ? e.requestFullscreen() : e.mozRequestFullScreen ? e.mozRequestFullScreen() : e.webkitRequestFullscreen ? e.webkitRequestFullscreen() : e.msRequestFullscreen && e.msRequestFullscreen()
                    }, n.exitFullscreen = function() {
                        t.exitFullscreen(), document.exitFullscreen ? document.exitFullscreen() : document.mozCancelFullScreen ? document.mozCancelFullScreen() : document.webkitExitFullscreen ? document.webkitExitFullscreen() : document.msExitFullscreen && document.msExitFullscreen()
                    }
                }(e),
                function(e) {
                    var t = e.core,
                        n = t.globalEventsController,
                        o = t.windowResizeActioner,
                        i = e.resolve,
                        r = i(q),
                        s = i(Y),
                        a = i(K);
                    n.attachListeners = function() {
                        document.addEventListener("mousemove", s.listener), document.addEventListener("touchmove", s.listener, {
                            passive: !0
                        }), document.addEventListener("mouseup", a.listener), document.addEventListener("touchend", a.listener, {
                            passive: !0
                        }), addEventListener("resize", o.runActions), document.addEventListener("keydown", r.listener)
                    }, n.removeListeners = function() {
                        document.removeEventListener("mousemove", s.listener), document.removeEventListener("touchmove", s.listener), document.removeEventListener("mouseup", a.listener), document.removeEventListener("touchend", a.listener), removeEventListener("resize", o.runActions), document.removeEventListener("keydown", r.listener)
                    }
                }(e),
                function(e) {
                    var t = e.core.lightboxCloser,
                        n = (0, e.resolve)(V);
                    t.closeLightbox = function() {
                        n.isLightboxFadingOut || n.runActions()
                    }
                }(e), ee(e),
                function(e) {
                    var t = e.data,
                        n = e.core.scrollbarRecompensor;
                    n.addRecompense = function() {
                        "complete" === document.readyState ? o() : addEventListener("load", (function() {
                            o(), n.addRecompense = o
                        }))
                    };
                    var o = function() {
                        document.body.offsetHeight > innerHeight && (document.body.style.marginRight = t.scrollbarWidth + "px")
                    };
                    n.removeRecompense = function() {
                        document.body.style.removeProperty("margin-right")
                    }
                }(e),
                function(e) {
                    var t = e.core,
                        n = t.slideChangeFacade,
                        o = t.slideIndexChanger,
                        i = t.stageManager;
                    e.props.sources.length > 1 ? (n.changeToPrevious = function() {
                        o.jumpTo(i.getPreviousSlideIndex())
                    }, n.changeToNext = function() {
                        o.jumpTo(i.getNextSlideIndex())
                    }) : (n.changeToPrevious = function() {}, n.changeToNext = function() {})
                }(e),
                function(e) {
                    var t, n, o = e.collections.sourceMainWrappersTransformers,
                        i = e.componentsServices,
                        r = e.core,
                        s = r.classFacade,
                        a = r.slideIndexChanger,
                        c = r.sourceDisplayFacade,
                        l = r.stageManager,
                        d = e.elements.sourceAnimationWrappers,
                        p = e.stageIndexes,
                        f = (t = function() {
                            s.removeFromEachElementClassIfContains("sourceAnimationWrappers", m)
                        }, n = [], function() {
                            n.push(!0), setTimeout((function() {
                                n.pop(), n.length || t()
                            }), 250)
                        });
                    a.changeTo = function(e) {
                        p.current = e, l.updateStageIndexes(), i.setSlideNumber(e + 1), c.displaySourcesWhichShouldBeDisplayed()
                    }, a.jumpTo = function(e) {
                        var t = p.current;
                        a.changeTo(e), s.removeFromEachElementClassIfContains("sourceMainWrappers", u), B(d[t], g), B(d[t], h), d[t].classList.add(m), B(d[e], g), B(d[e], m), d[e].classList.add(h), f(), o[e].zero(), setTimeout((function() {
                            t !== p.current && o[t].negative()
                        }), 220)
                    }
                }(e),
                function(e) {
                    var t = e.core,
                        n = t.classFacade,
                        o = t.slideSwipingDown,
                        i = e.elements.sources,
                        r = e.slideSwipingProps,
                        s = e.stageIndexes;
                    o.listener = function(e) {
                        r.isSwiping = !0, r.downClientX = U(e), r.swipedX = 0, "VIDEO" === e.target.tagName || e.touches || e.preventDefault();
                        var t = i[s.current];
                        t && t.contains(e.target) ? r.isSourceDownEventTarget = !0 : r.isSourceDownEventTarget = !1, n.removeFromEachElementClassIfContains("sourceMainWrappers", u)
                    }
                }(e),
                function(e) {
                    var t = e.collections.sourcesRenderFunctions,
                        n = e.core.sourceDisplayFacade,
                        o = e.props,
                        i = e.stageIndexes;

                    function r(e) {
                        t[e] && (t[e](), delete t[e])
                    }
                    n.displaySourcesWhichShouldBeDisplayed = function() {
                        if (o.loadOnlyCurrentSource) r(i.current);
                        else
                            for (var e in i) r(i[e])
                    }
                }(e),
                function(e) {
                    var t = e.stageIndexes,
                        n = e.core.stageManager,
                        o = e.props.sources.length - 1;
                    n.getPreviousSlideIndex = function() {
                        return 0 === t.current ? o : t.current - 1
                    }, n.getNextSlideIndex = function() {
                        return t.current === o ? 0 : t.current + 1
                    }, n.updateStageIndexes = 0 === o ? function() {} : 1 === o ? function() {
                        0 === t.current ? (t.next = 1, delete t.previous) : (t.previous = 0, delete t.next)
                    } : function() {
                        t.previous = n.getPreviousSlideIndex(), t.next = n.getNextSlideIndex()
                    }, n.isSourceInStage = o <= 2 ? function() {
                        return !0
                    } : function(e) {
                        var n = t.current;
                        if (0 === n && e === o || n === o && 0 === e) return !0;
                        var i = n - e;
                        return -1 === i || 0 === i || 1 === i
                    }
                }(e),
                function(e) {
                    var t = e.collections,
                        n = t.sourceMainWrappersTransformers,
                        o = t.sourceSizers,
                        i = e.core.windowResizeActioner,
                        r = e.data,
                        s = e.elements.sourceMainWrappers,
                        a = e.props,
                        c = e.stageIndexes;
                    i.runActions = function() {
                        innerWidth < 992 ? r.maxSourceWidth = innerWidth : r.maxSourceWidth = .9 * innerWidth, r.maxSourceHeight = .9 * innerHeight;
                        for (var e = 0; e < a.sources.length; e++) B(s[e], u), e !== c.current && n[e].negative(), o[e] && o[e].adjustSize()
                    }
                }(e)
        }

        function Z(e) {
            var t = e.core.eventsDispatcher,
                n = e.data,
                o = e.elements,
                r = e.props.sources;
            n.isInitialized = !0,
                function(e) {
                    for (var t = e.collections.sourceMainWrappersTransformers, n = e.props.sources, o = e.resolve, i = 0; i < n.length; i++) t[i] = o(P, [i])
                }(e), Q(e), o.container = document.createElement("div"), o.container.className = "".concat(i, "container ").concat(a, " ").concat(g),
                function(e) {
                    var t = e.elements;
                    t.slideSwipingHoverer = document.createElement("div"), t.slideSwipingHoverer.className = "".concat(i, "slide-swiping-hoverer ").concat(a, " ").concat(d)
                }(e), j(e),
                function(e) {
                    var t = e.core.slideSwipingDown,
                        n = e.elements,
                        o = e.props.sources;
                    n.sourceWrappersContainer = document.createElement("div"), n.sourceWrappersContainer.className = "".concat(d, " ").concat(a), n.container.appendChild(n.sourceWrappersContainer), n.sourceWrappersContainer.addEventListener("mousedown", t.listener), n.sourceWrappersContainer.addEventListener("touchstart", t.listener, {
                        passive: !0
                    });
                    for (var i = 0; i < o.length; i++) D(e, i)
                }(e), r.length > 1 && function(e) {
                    var t = e.core.slideChangeFacade;
                    X(e, t.changeToPrevious, "previous", "M18.271,9.212H3.615l4.184-4.184c0.306-0.306,0.306-0.801,0-1.107c-0.306-0.306-0.801-0.306-1.107,0L1.21,9.403C1.194,9.417,1.174,9.421,1.158,9.437c-0.181,0.181-0.242,0.425-0.209,0.66c0.005,0.038,0.012,0.071,0.022,0.109c0.028,0.098,0.075,0.188,0.142,0.271c0.021,0.026,0.021,0.061,0.045,0.085c0.015,0.016,0.034,0.02,0.05,0.033l5.484,5.483c0.306,0.307,0.801,0.307,1.107,0c0.306-0.305,0.306-0.801,0-1.105l-4.184-4.185h14.656c0.436,0,0.788-0.353,0.788-0.788S18.707,9.212,18.271,9.212z"), X(e, t.changeToNext, "next", "M1.729,9.212h14.656l-4.184-4.184c-0.307-0.306-0.307-0.801,0-1.107c0.305-0.306,0.801-0.306,1.106,0l5.481,5.482c0.018,0.014,0.037,0.019,0.053,0.034c0.181,0.181,0.242,0.425,0.209,0.66c-0.004,0.038-0.012,0.071-0.021,0.109c-0.028,0.098-0.075,0.188-0.143,0.271c-0.021,0.026-0.021,0.061-0.045,0.085c-0.015,0.016-0.034,0.02-0.051,0.033l-5.483,5.483c-0.306,0.307-0.802,0.307-1.106,0c-0.307-0.305-0.307-0.801,0-1.105l4.184-4.185H1.729c-0.436,0-0.788-0.353-0.788-0.788S1.293,9.212,1.729,9.212z")
                }(e),
                function(e) {
                    for (var t = e.props.sources, n = e.resolve, o = n(w), i = n(M), r = n(k, [o, i]), s = 0; s < t.length; s++)
                        if ("string" == typeof t[s]) {
                            var a = r.getTypeSetByClientForIndex(s);
                            if (a) i.runActionsForSourceTypeAndIndex(a, s);
                            else {
                                var c = o.getSourceTypeFromLocalStorageByUrl(t[s]);
                                c ? i.runActionsForSourceTypeAndIndex(c, s) : r.retrieveTypeWithXhrForIndex(s)
                            }
                        } else i.runActionsForSourceTypeAndIndex("custom", s)
                }(e), t.dispatch("onInit")
        }

        function ee(e) {
            var t = e.collections.sourceMainWrappersTransformers,
                n = e.componentsServices,
                o = e.core,
                i = o.eventsDispatcher,
                r = o.lightboxOpener,
                s = o.globalEventsController,
                a = o.scrollbarRecompensor,
                c = o.sourceDisplayFacade,
                u = o.stageManager,
                d = o.windowResizeActioner,
                p = e.data,
                f = e.elements,
                h = e.stageIndexes;
            r.open = function() {
                var o = arguments.length > 0 && void 0 !== arguments[0] ? arguments[0] : 0;
                h.current = o, p.isInitialized ? i.dispatch("onShow") : Z(e), u.updateStageIndexes(), c.displaySourcesWhichShouldBeDisplayed(), n.setSlideNumber(o + 1), document.body.appendChild(f.container), document.documentElement.classList.add(l), a.addRecompense(), s.attachListeners(), d.runActions(), t[h.current].zero(), i.dispatch("onOpen")
            }
        }

        function te() {
            var e = localStorage.getItem("fslightbox-scrollbar-width");
            if (e) return e;
            var t = function() {
                    var e = document.createElement("div"),
                        t = e.style;
                    return t.visibility = "hidden", t.width = "100px", t.msOverflowStyle = "scrollbar", t.overflow = "scroll", e
                }(),
                n = function() {
                    var e = document.createElement("div");
                    return e.style.width = "100%", e
                }();
            document.body.appendChild(t);
            var o = t.offsetWidth;
            t.appendChild(n);
            var i = n.offsetWidth;
            document.body.removeChild(t);
            var r = o - i;
            return localStorage.setItem("fslightbox-scrollbar-width", r.toString()), r
        }

        function ne(e, t, n) {
            return (ne = oe() ? Reflect.construct : function(e, t, n) {
                var o = [null];
                o.push.apply(o, t);
                var i = new(Function.bind.apply(e, o));
                return n && ie(i, n.prototype), i
            }).apply(null, arguments)
        }

        function oe() {
            if ("undefined" == typeof Reflect || !Reflect.construct) return !1;
            if (Reflect.construct.sham) return !1;
            if ("function" == typeof Proxy) return !0;
            try {
                return Date.prototype.toString.call(Reflect.construct(Date, [], (function() {}))), !0
            } catch (e) {
                return !1
            }
        }

        function ie(e, t) {
            return (ie = Object.setPrototypeOf || function(e, t) {
                return e.__proto__ = t, e
            })(e, t)
        }

        function re(e) {
            return function(e) {
                if (Array.isArray(e)) return se(e)
            }(e) || function(e) {
                if ("undefined" != typeof Symbol && Symbol.iterator in Object(e)) return Array.from(e)
            }(e) || function(e, t) {
                if (e) {
                    if ("string" == typeof e) return se(e, t);
                    var n = Object.prototype.toString.call(e).slice(8, -1);
                    return "Object" === n && e.constructor && (n = e.constructor.name), "Map" === n || "Set" === n ? Array.from(e) : "Arguments" === n || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n) ? se(e, t) : void 0
                }
            }(e) || function() {
                throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")
            }()
        }

        function se(e, t) {
            (null == t || t > e.length) && (t = e.length);
            for (var n = 0, o = new Array(t); n < t; n++) o[n] = e[n];
            return o
        }

        function ae() {
            for (var e = document.getElementsByTagName("a"), t = function(t) {
                    if (!e[t].hasAttribute("data-fslightbox")) return "continue";
                    var n = e[t].getAttribute("data-fslightbox"),
                        o = e[t].getAttribute("href");
                    fsLightboxInstances[n] || (fsLightboxInstances[n] = new FsLightbox);
                    var i;
                    i = "#" === o.charAt(0) ? document.getElementById(o.substring(1)) : o, fsLightboxInstances[n].props.sources.push(i), fsLightboxInstances[n].elements.a.push(e[t]);
                    var r = fsLightboxInstances[n].props.sources.length - 1;
                    e[t].onclick = function(e) {
                        e.preventDefault(), fsLightboxInstances[n].open(r)
                    }, d("types", "data-type"), d("videosPosters", "data-video-poster"), d("customClasses", "data-class"), d("customClasses", "data-custom-class");
                    for (var s = ["href", "data-fslightbox", "data-type", "data-video-poster", "data-class", "data-custom-class"], a = e[t].attributes, c = fsLightboxInstances[n].props.customAttributes, l = 0; l < a.length; l++)
                        if (-1 === s.indexOf(a[l].name) && "data-" === a[l].name.substr(0, 5)) {
                            c[r] || (c[r] = {});
                            var u = a[l].name.substr(5);
                            c[r][u] = a[l].value
                        }
                    function d(o, i) {
                        e[t].hasAttribute(i) && (fsLightboxInstances[n].props[o][r] = e[t].getAttribute(i))
                    }
                }, n = 0; n < e.length; n++) t(n);
            var o = Object.keys(fsLightboxInstances);
            window.fsLightbox = fsLightboxInstances[o[o.length - 1]]
        }
        "object" === ("undefined" == typeof document ? "undefined" : S(document)) && ((o = document.createElement("style")).className = r, o.appendChild(document.createTextNode(".fslightbox-absoluted{position:absolute;top:0;left:0}.fslightbox-fade-in{animation:fslightbox-fade-in .25s cubic-bezier(0,0,.7,1)}.fslightbox-fade-out{animation:fslightbox-fade-out .25s ease}.fslightbox-fade-in-strong{animation:fslightbox-fade-in-strong .25s cubic-bezier(0,0,.7,1)}.fslightbox-fade-out-strong{animation:fslightbox-fade-out-strong .25s ease}@keyframes fslightbox-fade-in{from{opacity:.65}to{opacity:1}}@keyframes fslightbox-fade-out{from{opacity:.35}to{opacity:0}}@keyframes fslightbox-fade-in-strong{from{opacity:.3}to{opacity:1}}@keyframes fslightbox-fade-out-strong{from{opacity:1}to{opacity:0}}.fslightbox-cursor-grabbing{cursor:grabbing}.fslightbox-full-dimension{width:100%;height:100%}.fslightbox-open{overflow:hidden;height:100%}.fslightbox-flex-centered{display:flex;justify-content:center;align-items:center}.fslightbox-opacity-0{opacity:0!important}.fslightbox-opacity-1{opacity:1!important}.fslightbox-scrollbarfix{padding-right:17px}.fslightbox-transform-transition{transition:transform .3s}.fslightbox-container{font-family:Arial,sans-serif;position:fixed;top:0;left:0;background:linear-gradient(rgba(30,30,30,.9),#000 1810%);z-index:1000000000;-webkit-user-select:none;-moz-user-select:none;-ms-user-select:none;user-select:none;-webkit-tap-highlight-color:transparent}.fslightbox-container *{box-sizing:border-box}.fslightbox-svg-path{transition:fill .15s ease;fill:#ddd}.fslightbox-nav{height:45px;width:100%;position:absolute;top:0;left:0}.fslightbox-slide-number-container{display:flex;justify-content:center;align-items:center;position:relative;height:100%;font-size:15px;color:#d7d7d7;z-index:0;max-width:55px;text-align:left}.fslightbox-slide-number-container .fslightbox-flex-centered{height:100%}.fslightbox-slash{display:block;margin:0 5px;width:1px;height:12px;transform:rotate(15deg);background:#fff}.fslightbox-toolbar{position:absolute;z-index:3;right:0;top:0;height:100%;display:flex;background:rgba(35,35,35,.65)}.fslightbox-toolbar-button{height:100%;width:45px;cursor:pointer}.fslightbox-toolbar-button:hover .fslightbox-svg-path{fill:#fff}.fslightbox-slide-btn-container{display:flex;align-items:center;padding:12px 12px 12px 6px;position:absolute;top:50%;cursor:pointer;z-index:3;transform:translateY(-50%)}@media (min-width:476px){.fslightbox-slide-btn-container{padding:22px 22px 22px 6px}}@media (min-width:768px){.fslightbox-slide-btn-container{padding:30px 30px 30px 6px}}.fslightbox-slide-btn-container:hover .fslightbox-svg-path{fill:#f1f1f1}.fslightbox-slide-btn{padding:9px;font-size:26px;background:rgba(35,35,35,.65)}@media (min-width:768px){.fslightbox-slide-btn{padding:10px}}@media (min-width:1600px){.fslightbox-slide-btn{padding:11px}}.fslightbox-slide-btn-container-previous{left:0}@media (max-width:475.99px){.fslightbox-slide-btn-container-previous{padding-left:3px}}.fslightbox-slide-btn-container-next{right:0;padding-left:12px;padding-right:3px}@media (min-width:476px){.fslightbox-slide-btn-container-next{padding-left:22px}}@media (min-width:768px){.fslightbox-slide-btn-container-next{padding-left:30px}}@media (min-width:476px){.fslightbox-slide-btn-container-next{padding-right:6px}}.fslightbox-down-event-detector{position:absolute;z-index:1}.fslightbox-slide-swiping-hoverer{z-index:4}.fslightbox-invalid-file-wrapper{font-size:22px;color:#eaebeb;margin:auto}.fslightbox-video{object-fit:cover}.fslightbox-youtube-iframe{border:0}.fslightbox-loader{display:block;margin:auto;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:67px;height:67px}.fslightbox-loader div{box-sizing:border-box;display:block;position:absolute;width:54px;height:54px;margin:6px;border:5px solid;border-color:#999 transparent transparent transparent;border-radius:50%;animation:fslightbox-loader 1.2s cubic-bezier(.5,0,.5,1) infinite}.fslightbox-loader div:nth-child(1){animation-delay:-.45s}.fslightbox-loader div:nth-child(2){animation-delay:-.3s}.fslightbox-loader div:nth-child(3){animation-delay:-.15s}@keyframes fslightbox-loader{0%{transform:rotate(0)}100%{transform:rotate(360deg)}}.fslightbox-source{position:relative;z-index:2;opacity:0}")), document.head.appendChild(o)), window.FsLightbox = function() {
            var e = this;
            this.props = {
                sources: [],
                customAttributes: [],
                customClasses: [],
                types: [],
                videosPosters: [],
                slideDistance: .3
            }, this.data = {
                isInitialized: !1,
                maxSourceWidth: 0,
                maxSourceHeight: 0,
                scrollbarWidth: te(),
                isFullscreenOpen: !1
            }, this.slideSwipingProps = {
                isSwiping: !1,
                downClientX: null,
                isSourceDownEventTarget: !1,
                swipedX: 0
            }, this.stageIndexes = {}, this.elements = {
                a: [],
                container: null,
                slideSwipingHoverer: null,
                sourceWrappersContainer: null,
                sources: [],
                sourceMainWrappers: [],
                sourceAnimationWrappers: []
            }, this.componentsServices = {
                enterFullscreen: null,
                exitFullscreen: null,
                hideSourceLoaderIfNotYetCollection: [],
                setSlideNumber: function() {}
            }, this.resolve = function(t) {
                var n = arguments.length > 1 && void 0 !== arguments[1] ? arguments[1] : [];
                return n.unshift(e), ne(t, re(n))
            }, this.collections = {
                sourceMainWrappersTransformers: [],
                sourceLoadHandlers: [],
                sourcesRenderFunctions: [],
                sourceSizers: []
            }, this.core = {
                classFacade: {},
                eventsDispatcher: {},
                fullscreenToggler: {},
                globalEventsController: {},
                lightboxCloser: {},
                lightboxOpener: {},
                lightboxUpdater: {},
                scrollbarRecompensor: {},
                slideChangeFacade: {},
                slideIndexChanger: {},
                slideSwipingDown: {},
                sourceDisplayFacade: {},
                stageManager: {},
                windowResizeActioner: {}
            }, ee(this), this.open = function(t) {
                return e.core.lightboxOpener.open(t)
            }, this.close = function() {
                return e.core.lightboxCloser.closeLightbox()
            }
        }, window.fsLightboxInstances = {}, ae(), window.refreshFsLightbox = function() {
            for (var e in fsLightboxInstances) {
                var t = fsLightboxInstances[e].props;
                fsLightboxInstances[e] = new FsLightbox, fsLightboxInstances[e].props = t, fsLightboxInstances[e].props.sources = [], fsLightboxInstances[e].elements.a = []
            }
            ae()
        }
    }])
}));