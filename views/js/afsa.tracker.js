/* global  aa, onAFSATrackerLoaded */

var AFSA = AFSA || {};




AFSA.tracker = {

    listenProductClick: function (p) {


    },

    sendProductClickByHttpReferral: function (p) {

    }

};


(function () {


    if (typeof onAFSATrackerLoaded === "function") {
        onAFSATrackerLoaded();
    }


})();