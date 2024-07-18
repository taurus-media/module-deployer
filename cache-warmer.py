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

num_cpus = os.cpu_count()
pause_workers = False

def load_watcher():
    global pause_workers
    while True:
        cpu_load = psutil.cpu_percent(interval=1)
        if cpu_load > 70.0:
            pause_workers = True
        else:
            pause_workers = False
        time.sleep(1)

def parse_sitemap(sitemap_url):
    response = requests.get(sitemap_url)
    response.raise_for_status()

    root = ET.fromstring(response.text)

    if root.tag.endswith('sitemapindex'):
        sitemap_urls = [url.text for url in root.iter('{http://www.sitemaps.org/schemas/sitemap/0.9}loc')]
        urls = []
        for url in sitemap_urls:
            urls.extend(parse_sitemap(url))
        return urls

    urls = [url.text for url in root.iter('{http://www.sitemaps.org/schemas/sitemap/0.9}loc')]

    return urls

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
        print(f"Error fetching {url}: {e}")
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

    with ThreadPoolExecutor(max_workers=num_cpus) as executor:
        results = list(executor.map(worker, urls))

    with open('results.csv', 'w', newline='') as file:
        writer = csv.writer(file)
        writer.writerow(["URL", "Status Code", "Load Time Before", "Load Time After", "Load Time Difference", "Optimized"])
        writer.writerows(results)

if __name__ == "__main__":
    main()
