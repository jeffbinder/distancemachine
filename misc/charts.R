d <- read.delim("wright.txt");

d1M <- read.delim("wright_gt1000000.txt");
d$word_count_1M <- d1M$word_count;
d$word_types_1M <- d1M$word_types;
d$nnew_tok_1M <- d1M$nnew_tok;
d$nnew_typ_1M <- d1M$nnew_typ;

d100k <- read.delim("wright_gt100000.txt");
d$word_count_100k <- d100k$word_count;
d$word_types_100k <- d100k$word_types;
d$nnew_tok_100k <- d100k$nnew_tok * d100k$words_above_freq / d100k$word_count;
d$nnew_typ_100k <- d100k$nnew_typ * d100k$word_types_above_freq / d100k$word_types;

d10k <- read.delim("wright_gt10000.txt");
d$word_count_10k <- d10k$word_count;
d$word_types_10k <- d10k$word_types;
d$nnew_tok_10k <- d10k$nnew_tok;
d$nnew_typ_10k <- d10k$nnew_typ;

for (y in unique(d$year)) {
  d$nnew_tok_scaled[d$year==y] <- scale(d$nnew_tok[d$year==y]);
  d$nnew_typ_scaled[d$year==y] <- scale(d$nnew_typ[d$year==y]);
  d$nnew_tok_1M_scaled[d$year==y] <- scale(d$nnew_tok_1M[d$year==y]);
  d$nnew_typ_1M_scaled[d$year==y] <- scale(d$nnew_typ_1M[d$year==y]);
  d$nnew_tok_100k_scaled[d$year==y] <- scale(d$nnew_tok_100k[d$year==y]);
  d$nnew_typ_100k_scaled[d$year==y] <- scale(d$nnew_typ_100k[d$year==y]);
  d$nnew_tok_10k_scaled[d$year==y] <- scale(d$nnew_tok_10k[d$year==y]);
  d$nnew_typ_10k_scaled[d$year==y] <- scale(d$nnew_typ_10k[d$year==y]);
}

plot(d$nnew_typ, d$nnew_typ_1M);

par(mfrow=c(2,2));

plot(d$year, d$nnew_tok_scaled);
plot(d$year, d$nnew_typ_scaled);
plot(d$nnew_tok_scaled, d$nnew_typ_scaled);
hist(d$nnew_typ, breaks=50);

plot(d$year, d$nnew_tok_1M_scaled);
plot(d$year, d$nnew_typ_1M_scaled);
plot(d$nnew_tok_1M_scaled, d$nnew_typ_1M_scaled);
hist(d$nnew_typ_1M, breaks=50);

plot(d$year, d$nnew_tok_100k_scaled);
plot(d$year, d$nnew_typ_100k_scaled);
plot(d$nnew_tok_100k_scaled, d$nnew_typ_100k_scaled);
hist(d$nnew_typ_100k, breaks=50);

plot(d$year, d$nnew_tok_10k_scaled);
plot(d$year, d$nnew_typ_10k_scaled);
plot(d$nnew_tok_10k_scaled, d$nnew_typ_10k_scaled);
hist(d$nnew_typ_10k, breaks=50);

par(mfrow=c(1,1));
