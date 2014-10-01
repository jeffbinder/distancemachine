window.start_year = 1800;
window.end_year = new Date().getFullYear();
window.data_start_year = 1750;
window.data_end_year = 2009;
window.min_freq = 10000000000;
window.max_freq = 1000000;

window.current_year = 1985;
window.first_line_visible = -1;
window.last_line_visible = -1;
window.prev_first_line_visible = -1;
window.prev_last_line_visible = -1;

window.corpus_names = {
    "gb" : "UK English",
    "us" : "US English"
};

// In order of appearance.
window.dicts = [];
//window.dicts = ["dict1", "dict2"];

// The first name is used in the "not available" message, the other in the heading.
window.dict_names = {
    "dict1" : ["Dictionary 1", "Dictionary 1 (1850)"],
    "dict2" : ["Dictionary 2", "Dictionary 2 (1900)"]
};