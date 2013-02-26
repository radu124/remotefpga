#!/usr/bin/python -B
from __future__ import print_function

import os
import time
import string
import shutil
from threading import Thread
import serial
import traceback

tasklist={}

class Task:
	pass

class Board:
	def __init__(self,pars):
		self.inuse=False
		self.typ=pars[0]
		self.name=pars[1]
		self.tty=pars[2]
		self.progthread=None
		self.listenthread=None

class ListenerThread(Thread):
	def __init__(self,task,b):
		self.task=task
		self.b=b
		Thread.__init__(self)
	def run(self):
		b=self.b
		task=self.task
		bytecount=0
		# wait first so that we don't get any garbage from a previous task
		# assume it takes at least 1 second to program the FPGA
		time.sleep(1)
		try:
			ser=serial.Serial(port = b.tty, baudrate=task.baudrate,timeout=1)
			fou=open("%s/ttyout"%task.fn,"w")
			starttime=time.time()
			alls=""
			lasttime=time.time()
			while True:
				timenow=time.time()
				if timenow-lasttime>0.2:
					fou.flush()
					lasttime=timenow
				if timenow-starttime>task.timeout:
					break
				s=ser.read(1) # should be more but FTDI TTY driver is buggy
				bytecount+=len(s)
				alls+=s
				alls=alls[-580:0]
				fou.write(s)
				if "\nFINISHED!\n" in alls:
					break;
				if "\n\rFINISHED!\n\r" in alls:
					break;
			ser.close()
		except:
			task.error=True
			ferr=open("%s/error"%task.fn, 'w')
			traceback.print_exc()
			ferr.close()
			pass
		try:
			open("%s/finished"%task.fn, 'w').close()
		except:
			traceback.print_exc()
		print("Task %s finished %d bytes written"%(task.fn,bytecount))
		b.listenthread=None
		b.inuse=False
		task.finished=True
		task.finishtime=time.time()

def put_to_file(fn,s):
	try:
		f=open(fn,"w")
		f.write(s)
		f.close()
	except:
		traceback.print_exc()
		
class ProgThread(Thread):
	def __init__(self,fn,boardname):
		self.fn=fn
		self.boardname=boardname
		Thread.__init__(self)
	def run(self):
		os.system("djtgcfg -d %s -i 0 -f %s/bit.bit prog"%(self.boardname,self.fn))

BOARDS=[ Board(x.strip().split(' ')) for x in open("boards").readlines() if x.strip()!="" and x.strip()[0]!="#" ]

lasttaskswaiting=0

while True:
	l=[ x for x in os.listdir('.') if not x.startswith('.') and os.path.isdir(x) ]
	for fn in tasklist.keys():
		tasklist[fn].touch=False
	for fn in l:
		if fn not in tasklist:
			nt=Task()
			tasklist[fn]=nt
			nt.starttime=os.stat(fn).st_mtime;
			print("Added task %s time %d"%(fn,nt.starttime))
			nt.baudrate=9600
			nt.userid="unknown"
			nt.boardtype="unknown"
			nt.timeout=20
			try:
				info=[ x.strip().split('=') for x in open("%s/info"%fn).readlines() if '=' in x ]
				for i in info:
					var=i[0].strip().lower()
					val=i[1].strip()
					if var=='baudrate':
						nt.baudrate=int(val)
					elif var=='userid':
						nt.userid=val
					elif var=='boardtype':
						nt.boardtype=val
			except:
				traceback.print_exc()
			nt.fn=fn
			nt.started=os.path.isfile("%s/finished"%fn)
			nt.finished=os.path.isfile("%s/finished"%fn)
			nt.finishtime=1e20
			if nt.finished:
				nt.finishtime=os.stat("%s/finished"%fn).st_mtime;
			nt.error=os.path.isfile("%s/error"%fn)
			if nt.finished:
				print("Task %s is already done"%(fn))
		tasklist[fn].touch=True
	taskswaiting=0
	for fn in sorted(tasklist.keys(),key=lambda x:tasklist[x].starttime):
		task=tasklist[fn]
		if not task.touch:
			print("Removed task %s as the directory is gone"%fn)
			del tasklist[fn]
			continue
		timenow=time.time()
		if timenow-task.starttime>3600:
			print("Task %s is more than 1 hour old (%d seconds), deleting"%(fn,timenow-task.starttime))
			shutil.rmtree("./%s"%fn)
			continue
		if timenow-task.finishtime>600:
			print("Task %s has finished more than 10 minutes ago (%d seconds), deleting"%(fn,timenow-task.starttime))
			shutil.rmtree("./%s"%fn)
			continue
		if os.path.isfile("%s/remove"):
			print("Task %s is marked for removal, deleting"%(fn))
			shutil.rmtree("./%s"%fn)
			continue
		if task.started or task.error:
			continue
		jobstarted=False
		for b in BOARDS:
			if b.inuse or b.typ!=task.boardtype:
				continue
			print("Found a suitable board for task %s, starting"%(fn))
			b.inuse=True
			task.started=True
			b.listenthread=ListenerThread(task,b)
			b.progthread=ProgThread(fn,b.name)
			b.listenthread.start()
			b.progthread.start()
			jobstarted=True
			break
		if jobstarted:
			continue
		put_to_file("%s/inqueue"%fn,str(taskswaiting))
		taskswaiting+=1
	if taskswaiting!=lasttaskswaiting:
		print("Tasks waiting:",taskswaiting)
		lasttaskswaiting=taskswaiting
	time.sleep(1)
