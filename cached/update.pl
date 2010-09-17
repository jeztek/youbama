#!/usr/bin/perl -w
use strict;

my $max_page = 50;

umask 0;

open LOG, ">>/tmp/update.log";
select LOG;
$|=1;

while (1) {
    update();
    sleep 10;
}

sub update {
    print "Update starting at ", scalar(localtime()), "\n";
    for (my $page = 1; $page <= $max_page; $page++) {
	mkdir "/var/www/youbama/cached/home/$page";
	http_copy("http://youbama.com/dhome/$page", "/var/www/youbama/cached/home/$page/index.html");
	mkdir "/var/www/youbama/cached/popular/$page";
	http_copy("http://youbama.com/dpopular/$page", "/var/www/youbama/cached/popular/$page/index.html");
	mkdir "/var/www/youbama/cached/newest/$page";
	http_copy("http://youbama.com/dnewest/$page", "/var/www/youbama/cached/newest/$page/index.html");
    }
    print "Update ending at ", scalar(localtime()), "\n";
}

sub http_copy {
    my ($url, $dest)=@_;
    my $html = http_get($url);
    open OUT, ">$dest.tmp";
    print OUT $html;
    close OUT;
    rename "$dest.tmp", "$dest" or print "Couldn't rename to $dest\n";
    print "Wrote $dest\n";
}
    
sub http_get {
    my ($url)=@_;
    print "Fetching $url...";
    my $ret = `wget --quiet --output-document=- $url`;
    print " got ", length($ret), " bytes\n";
    return $ret;
}

