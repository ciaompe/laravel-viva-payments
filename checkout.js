//@ts-check

/**
 * @typedef Container
 * @type {Object}
 * @property {JQuery} expdate
 * @property {JQuery} month
 * @property {JQuery} year
 * @property {JQuery} cardnumber
 * @property {JQuery} cardholder
 * @property {JQuery} friendlyname
 * @property {JQuery} token
*/

/**
 * @typedef Cards
 * @type {Object}
 * @property {string} cardNumber
 * @property {Settings} settings
 * @property {function} setup
 * @property {function} requestToken
 * @property {function} triggerCallback
 * @property {function} setupCardNumberElement
 * @property {function} headers
*/

/**
 * @typedef Settings
 * @type {Object}
 * @property {SetupOptions} options
 * @property {String[]} requiredDataAttrs
 * @property {String[]} optionalDataAttrs
*/

/**
 * @typedef SetupOptions
 * @type {Object}
 * @property {string} authToken
 * @property {CardHolderAuthOptions} cardHolderAuthOptions
 * @property {function} installmentsHandler
 * @property {string} baseURL
*/

/**
 * @typedef CardHolderAuthOptions
 * @type {Object}
 * @property {string} cardHolderAuthPlaceholderId
 * @property {function} cardHolderAuthInitiated
 * @property {function} cardHolderAuthFinished
*/

/**
 * @typedef RequestTokenOptions
 * @type {Object}
 * @property {number} amount
 * @property {number} installments
 * @property {boolean} authenticateCardholder
*/

/**
 * @typedef VivaPayments
 * @type {Object}
 * @property {number} version
 * @property {number} NativeCheckoutVersion
 * @property {Object} statusCode
 * @property {MessageType} MessageType
 * @property {any} MessageData
 * @property {function} PaymentsMessage
 * @property {function} isMobileSafari
 * @property {function} getURLs
 * @property {function} setBaseURL
 * @property {Cards} cards
*/

/**
 * @typedef MessageType
 * @type {Object}
 * @property {string} THREEDSECURE
 */

function trimAll(string) {
    return string.replace(/\s+/g, "");
}

function logPrefix(_api, n) {
    return _api.logPrefix.concat(n)
}

/**
 * @param {String} redirectToACSForm
 * @param {String} iframeId
 */
function renderIframe(redirectToACSForm, iframeId) {
    if (redirectToACSForm && iframeId) {
        var $div = jQuery("<div/>").html(redirectToACSForm).html();
        /** @type {HTMLIFrameElement} */
        var iframe = document.getElementById(iframeId);
        iframe = iframe.contentWindow || iframe.contentDocument.document || iframe.contentDocument;
        iframe.document.open();
        iframe.document.write($div);
        iframe.document.close()
    }
}

class VivaError {
    /**
     * @param {number} errorCode
     * @param {string} errorText
     */
    constructor(errorCode, errorText) {
        this.ErrorCode = errorCode;
        this.ErrorText = logPrefix(_api, errorText);
    }

    toString() {
        return this.ErrorText;
    }
}

/**
 * @param {number} errorCode
 * @param {string} errorText
 * @returns {object}
 */
function createError(errorCode, errorText) {
    return {
        Error: new VivaError(errorCode,errorText),
    }
}

/**
 * @param {string} month
 * @param {string} year
 */
function formatDate(month, year) {
    return (
        year = year.toString(),
        month = month.toString(),
        !/^(\d{2}|\d{4})$/.test(year) || !/^\d{1,2}$/.test(month)
    )
        ? ''
        : (year.length === 2 ? "20" + year : year) + "-" + (month.length === 1 ? "0" + month : month) + "-15"
}

/**
 * @param {string} cardNumber
 * @returns {Boolean}
 */
function validateCardNumber(cardNumber) {
    return /^\d/.test(cardNumber)
}

import jQuery from 'jquery';

if (typeof jQuery == "undefined") {
    throw "VivaPayments.js requires jQuery";
}

const _api = {
    baseURL: "https://api.vivapayments.com",
    logPrefix: "VivaPayments: "
};

/** @type {Cards} */
const cards = {
    settings: {
        requiredDataAttrs: ["cardnumber", "cardholder", "cvv", "expdate", "month", "year"],
        optionalDataAttrs: ["friendlyname"],
        options: {
            authToken: undefined,
            cardHolderAuthOptions: undefined,
            installmentsHandler: undefined,
            baseURL: undefined,
        },
    },

    /**
     * @param {string} callback
     */
    triggerCallback(callback) {
        var cardHolderAuthOptions = this.settings.options.cardHolderAuthOptions;
        if (typeof cardHolderAuthOptions != "undefined" && typeof cardHolderAuthOptions[callback] == "function") {
            cardHolderAuthOptions[callback]();
        }
    },

    cardNumber: undefined,

    /**
     * @param {SetupOptions} setupOptions
     */
    setup(setupOptions) {
        if (typeof setupOptions == "undefined") {
            throw new VivaError(vivaPayments.statusCode.BAD_REQUEST, "Null options");
        }

        this.settings.options = {
            authToken: setupOptions.authToken,
            baseURL: setupOptions.baseURL || _api.baseURL,
            installmentsHandler: setupOptions.installmentsHandler,
            amount: setupOptions.amount,
            cardHolderAuthOptions: setupOptions.cardHolderAuthOptions
        };

        vivaPayments.setBaseURL(this.settings.options.baseURL);

        validateOptions(this.settings.options, vivaPayments, $elements);

        this.setupCardNumberElement($elements);
    },

    /**
     * @param {Container} $elements
     */
    setupCardNumberElement($elements) {
        var $cardNumberElement = $elements.cardnumber;

        var self = this;

        if (this.settings.options.installmentsHandler) {
            $cardNumberElement.on("blur", function() {
                var cardNumber = trimAll(jQuery(this).val());
                var _headers;
                cardNumber !== self.cardNumber && validateCardNumber(cardNumber) && (_headers = jQuery.extend({
                    cardNumber: cardNumber
                }, self.headers()),
                jQuery.ajax({
                    url: _api.urls.installments,
                    method: "get",
                    headers: _headers
                }).done((response) => {
                    cards.settings.options.installmentsHandler({ MaxInstallments: response.maxInstallments });
                }).fail((error) => {
                    cards.settings.options.installmentsHandler(createError(error.status, error.statusText))
                }).always((response) => {
                    self.cardNumber = response.maxInstallments ? cardNumber : undefined
                }))
            });
        }
    },

    headers() {
        let settings = this.settings;
        return {
            "x-viva-native-version": VivaPayments.NativeCheckoutVersion,
            Authorization: "Bearer " + settings.options.authToken,
        }
    },

    /**
     * @param {RequestTokenOptions} requestTokenOptions
     */
    requestToken(requestTokenOptions) {
        var self = this;

        /** @type {string} month */
        var month;

        /** @type {string} year */
        var year;

        var $deferred = jQuery.Deferred();

        /** @type {[string, string]} month, year */
        var monthYear;

        /** @type {string} */
        var formattedDate;

        /** @type {object} */
        var _data;

        for (let containerElement in $elements) {
            $elements[containerElement].removeAttr("name");
        }

        if (!parseInt(requestTokenOptions.amount) || requestTokenOptions.amount <= 0) {
            return $deferred.reject(createError(vivaPayments.statusCode.BAD_REQUEST, "Invalid amount")).promise();
        }

        if (typeof $elements.expdate != "undefined") {
            if (monthYear = trimAll($elements.expdate.val()).split("/"),
            monthYear.length !== 2) {
                return $deferred.reject(createError(vivaPayments.statusCode.BAD_REQUEST, "Expiration date could not be parsed")).promise();
            }
            month = monthYear[0];
            year = monthYear[1]
        } else {
            month = $elements.month.val(),
            year = $elements.year.val();
        }
        let cardTokenUrl;
        let __headers = this.headers();
        return (formattedDate = formatDate(month, year), formattedDate == '')
            ? $deferred.reject(createError(vivaPayments.statusCode.BAD_REQUEST, "Expiration Date information could not be parsed")).promise()
            : (_data = {
                Number: trimAll($elements.cardnumber.val()),
                CVC: $elements.cvv.val(),
                HolderName: $elements.cardholder.val(),
                ExpirationYear: year,
                ExpirationMonth: month,
                Installments: requestTokenOptions.installments,
                Amount: requestTokenOptions.amount,
                AuthenticateCardholder: requestTokenOptions.authenticateCardholder
            },
        typeof $elements.friendlyname != "undefined" && (_data.FriendlyName = $elements.friendlyname.val()),
        typeof $elements.token != "undefined" && (_data.Token = $elements.token.val()),
        !validateCardNumber(_data.Number))
            ? $deferred.reject(createError(vivaPayments.statusCode.BAD_REQUEST, "Invalid card number")).promise()
            : _data.HolderName.length === 0 ? $deferred.reject(createError(vivaPayments.statusCode.BAD_REQUEST, "Cardholder cannot be empty")).promise() : (typeof _data.AuthenticateCardholder == "undefined" && (_data.AuthenticateCardholder = !0),
        cardTokenUrl = _api.urls.cardtoken,
        _data.AuthenticateCardholder === !1 && (cardTokenUrl += ":skipauth"),
        jQuery.ajax({
            url: cardTokenUrl,
            type: "POST",
            contentType: "application/json",
            headers: __headers,
            data: JSON.stringify(_data)
        }).done(function({ chargeToken, redirectToACSForm }) {
            if (!redirectToACSForm) {
                $deferred.resolve({ chargeToken });
                return;
            }

            jQuery("#threed-secure-frame").remove();

            /** @type {JQuery} */
            const $iframe = jQuery('<iframe id="threed-secure-frame" frameBorder="0">');
            $iframe.css("height", "100%");
            $iframe.css("width", "100%");
            jQuery("#" + self.settings.options.cardHolderAuthOptions.cardHolderAuthPlaceholderId).append($iframe);
            renderIframe(redirectToACSForm, "threed-secure-frame");
            self.triggerCallback("cardHolderAuthInitiated");

            jQuery(window).on("message", (event) => {
                var eventData = JSON.parse(event.originalEvent.data);

                if (
                    eventData.MessageType === vivaPayments.MessageType.THREEDSECURE &&
                    $deferred.state() !== "resolved"
                ) {
                    self.triggerCallback("cardHolderAuthFinished");
                    $deferred.resolve({ chargeToken });
                }
            });
        }).fail((error) => {
            $deferred.reject(createError(error.status, error.statusText))
        }),
        $deferred.promise())
    }
};

/** @type {VivaPayments} */
const vivaPayments = {
    version: 230,
    NativeCheckoutVersion: 200,
    statusCode: {
        OK: 200,
        LOCKED: 423,
        NOT_FOUND: 404,
        BAD_REQUEST: 400
    },
    MessageType: {
        THREEDSECURE: "vp_sc_threedsecure"
    },
    getURLs() {
        return {
            cardtoken: _api.baseURL.concat("/nativecheckout/v2/chargetokens"),
            installments: _api.baseURL.concat("/nativecheckout/v2/installments")
        };
    },
    /**
     * @param {string} baseUrl
     */
    setBaseURL(baseUrl) {
        baseUrl = baseUrl[baseUrl.length - 1] === "/" ? baseUrl.substring(0, baseUrl.length - 1) : baseUrl;
        _api.baseURL = baseUrl;
        _api.urls = this.getURLs();
    },
    isMobileSafari() {
        var userAgent = navigator.userAgent;
        return /(iPhone|iPad|iPod)/i.test(userAgent) && /Safari/i.test(userAgent)
    },
    cards: cards,
};

_api.urls = vivaPayments.getURLs();

vivaPayments.PaymentsMessage = function(messageType, messageData) {
    this.MessageType = messageType;
    this.MessageData = messageData;
};

/** @type {Container} */
const $elements = {};

/**
 * @param {SetupOptions} options
 * @param {VivaPayments} vivaPayments
 * @param {Container} $elements
 */
function validateOptions(options, vivaPayments, $elements) {
    if (typeof options == "undefined")
        throw new VivaError(vivaPayments.statusCode.BAD_REQUEST,"No options have been set");
    if (!options.authToken || options.authToken.length === 0)
        throw new VivaError(vivaPayments.statusCode.BAD_REQUEST,"Authorization token not set");
    if (options.installmentsHandler && typeof options.installmentsHandler != "function")
        throw new VivaError(vivaPayments.statusCode.BAD_REQUEST,"installmentsHandler is not a function");
    if (options.cardHolderAuthOptions) {
        if (jQuery("#" + options.cardHolderAuthOptions.cardHolderAuthPlaceholderId).length === 0)
            throw new VivaError(vivaPayments.statusCode.BAD_REQUEST,"cardHolderAuthPlaceholderId is not defined");
        if (options.cardHolderAuthOptions.cardHolderAuthInitiated && typeof options.cardHolderAuthOptions.cardHolderAuthInitiated != "function")
            throw new VivaError(vivaPayments.statusCode.BAD_REQUEST,"cardHolderAuthInitiated is not a function");
        if (options.cardHolderAuthOptions.cardHolderAuthFinished && typeof options.cardHolderAuthOptions.cardHolderAuthFinished != "function")
            throw new VivaError(vivaPayments.statusCode.BAD_REQUEST,"cardHolderAuthFinished is not a function");
    }
    var i = !1;

    jQuery.each(vivaPayments.cards.settings.requiredDataAttrs, function(index, requiredDataAttribute) {
        var $requiredElement;
        if (requiredDataAttribute !== "month" && requiredDataAttribute !== "year" || !i)
            if (($requiredElement = jQuery(`[data-vp="${requiredDataAttribute}"]`)).length === 0) {
                if (requiredDataAttribute === "expdate") {
                    return;
                }
                throw new VivaError(vivaPayments.statusCode.NOT_FOUND,`Required data-vp attribute '${requiredDataAttribute}' was not found`);
            } else if ($requiredElement.length > 1) {
                throw new VivaError(vivaPayments.statusCode.BAD_REQUEST,`Attribute violation. data-vp '${requiredDataAttribute}' was found multiple times`);
            } else {
                requiredDataAttribute === "expdate" && (i = !0),
                $requiredElement.removeAttr("name"),
                $elements[requiredDataAttribute] = $requiredElement;
            }
    });

    jQuery.each(vivaPayments.cards.settings.optionalDataAttrs, function(n, optionalDataAttr) {
        var $optionalElement;
        if (($optionalElement = jQuery(`[data-vp="${optionalDataAttr}"]`)).length === 1) {
            $elements[optionalDataAttr] = $optionalElement;
        } else if ($optionalElement.length > 1) {
            throw new VivaError(vivaPayments.statusCode.BAD_REQUEST, `Attribute violation. data-vp '${optionalDataAttr}' was found multiple times`);
        }
    })
}

