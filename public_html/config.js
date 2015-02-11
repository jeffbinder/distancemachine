window.start_year = {'us': 1800, 'gb': 1800, 'eebotcp1': 1500};
window.end_year = {'us': new Date().getFullYear(), 'gb': new Date().getFullYear(),
                   'eebotcp1': 1700};
window.data_start_year = {'us': 1750, 'gb': 1750, 'eebotcp1': 1500};
window.data_end_year = {'us': 2009, 'gb': 2009, 'eebotcp1': 1700};
window.min_freq = 10000000000;
window.max_freq = 1000000;

window.current_year = 1985;
window.first_line_visible = -1;
window.last_line_visible = -1;
window.prev_first_line_visible = -1;
window.prev_last_line_visible = -1;

window.corpus_names = {
    "gb" : "Google Books UK English, 1750-2009",
    "us" : "Google Books US English, 1750-2009",
    "eebotcp1" : "EEBO-TCP Phase I: 25,000 English books, 1500-1700"
};

// In order of appearance.
window.dicts = [];
//window.dicts = ["dict1", "dict2"];

// The first name is used in the "not available" message, the other in the heading.
window.dict_names = {
    "dict1" : ["Dictionary 1", "Dictionary 1 (1850)"],
    "dict2" : ["Dictionary 2", "Dictionary 2 (1900)"]
};
