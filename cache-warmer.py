import sys
import requests
import xml.etree.ElementTree as ET
import csv
from time import time as timer
from concurrent.futures import ThreadPoolExecutor
import os
import psutil
import threading
import time
import re

try:
    num_cpus = os.cpu_count()
except AttributeError:
    num_cpus = psutil.cpu_count()

pause_workers = False
stop_watcher = False  # Add a flag to stop the watcher

def load_watcher():
    global pause_workers, stop_watcher
    while not stop_watcher:
        cpu_load = psutil.cpu_percent(interval=1)
        pause_workers = cpu_load > 70.0
        time.sleep(1)

def parse_sitemap(filename):
    with open(filename) as fh:
        return re.findall('<loc>(.*?)</loc>?', fh.read())

def worker(url):
    global pause_workers
    while pause_workers:
        time.sleep(1)

    load_time_before = None
    load_time_after = None
    load_time_diff = None
    load_time_diff_percent = None
    optimized = 'n/a'
    try:
        start_time = timer()
        response = requests.get(url, timeout=10)
        end_time = timer()
        load_time_before = end_time - start_time

        start_time = timer()
        response = requests.get(url, timeout=10)
        end_time = timer()
        load_time_after = end_time - start_time

        load_time_diff = load_time_before - load_time_after
        load_time_diff_percent = (load_time_diff / load_time_before) * 100 if load_time_before else None
        optimized = 'Yes' if load_time_diff_percent and load_time_diff_percent > 20 else 'No'

        status_code = response.status_code
    except requests.RequestException as e:
        print("Error fetching {}: {}".format(url, e))
        status_code = 'error'
    return (url, status_code, load_time_before, load_time_after, load_time_diff, optimized)

def main():
    if len(sys.argv) != 2:
        print("Gebruik: python script.py <sitemap_url>")
        sys.exit(1)
    sitemap_url = sys.argv[1]

    urls = parse_sitemap(sitemap_url)

    load_watcher_thread = threading.Thread(target=load_watcher)
    load_watcher_thread.start()

    try:
        with ThreadPoolExecutor(max_workers=num_cpus) as executor:
            results = list(executor.map(worker, urls))
    finally:
        global stop_watcher
        stop_watcher = True
        load_watcher_thread.join()

    with open('~/.dep/cache_warmer_results.csv', 'wb') as file:  # Open the file in binary mode
        writer = csv.writer(file)
        writer.writerow(["URL", "Status Code", "Load Time Before", "Load Time After", "Load Time Difference", "Optimized"])
        writer.writerows(results)

if __name__ == "__main__":
    main()
