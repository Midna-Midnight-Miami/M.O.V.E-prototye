use std::collections::HashMap;
use std::error::Error;
use std::io;
use mysql::Opts;
use mysql::prelude::*;
use chrono::{Utc, NaiveDateTime, Duration};

const DB_HOST: &str = "localhost";
const DB_PORT: u16 = 3306;
const DB_USERNAME: &str = "root";
const DB_PASSWORD: &str = "H3rm!tsus";
const DB_NAME: &str = "campus_management";

fn main() {
    let mut student_statuses = load_students().unwrap();
    let mut scanner_in = String::new();

    loop {
        scanner_in.clear();
        io::stdin().read_line(&mut scanner_in).unwrap();
        let input = scanner_in.trim();

        match input {
            "R" => handle_r(&mut student_statuses),
            "C" | "c" | "W" | "O" => handle_out(&mut student_statuses, input),
            _ => handle_ID(&mut student_statuses, input),
        }
    }
}

fn handle_r(student_statuses: &mut HashMap<i32, String>) {
    if student_statuses.values().any(|v| v == "R") {
        println!("Go poop outside");
        return;
    }

    println!("Scan your ID");
    let student_id = read_student_id();
    if let Some(status) = student_statuses.get(&student_id) {
        if status != "I" {
            println!("You're not even real");
            return;
        }

        if let Err(err) = insert_log_record(student_id, "R", 0) {
            println!("Error inserting log record: {}", err);
        } else {
            student_statuses.insert(student_id, "R".to_string());
            println!("Inserted log record for student: {}", student_id);
        }
    } else {
        println!("Student ID not found");
    }
}

fn handle_out(student_statuses: &mut HashMap<i32, String>, reason: &str) {
    println!("Scan ID and get out of here");
    let student_id = read_student_id();
    if let Some(status) = student_statuses.get(&student_id) {
        if status != "I" {
            println!("You're not in class?");
            return;
        }

        if let Err(err) = insert_log_record(student_id, reason, 0) {
            println!("Error inserting log record: {}", err);
        } else {
            student_statuses.insert(student_id, reason.to_string());
            println!("Inserted log record for student: {}", student_id);
        }
    }
    println!("{}", student_id);
}

fn handle_ID(student_statuses: &mut HashMap<i32, String>, input: &str) {
    if let Ok(student_id) = input.parse::<i32>() {
        if let Some(status) = student_statuses.get(&student_id) {
            if status == "I" {
                if let Err(err) = insert_log_record(student_id, "E", 0) {
                    println!("Error inserting log record: {}", err);
                }
                student_statuses.insert(student_id, "E".to_string());
                println!("{}", input);
                println!("Early departure? Better have a reason");
            } else {
                if let Err(err) = insert_log_record(student_id, "I", 0) {
                    println!("Error inserting log record: {}", err);
                }
                student_statuses.insert(student_id, "I".to_string());
                println!("{}", input);
                println!("Welcome");
            }
        }
    }
}

fn read_student_id() -> i32 {
    let mut scanner_in = String::new();
    io::stdin().read_line(&mut scanner_in).unwrap();
    scanner_in.trim().parse::<i32>().unwrap()
}

fn load_students() -> Result<HashMap<i32, String>, Box<dyn Error>> {
    let mut student_statuses = HashMap::new();
    let opts = Opts::from_url(&format!(
        "mysql://{}:{}@{}:{}/{}",
        DB_USERNAME, DB_PASSWORD, DB_HOST, DB_PORT, DB_NAME
    ))?;
    let mut pool = mysql::Pool::new(opts)?;
    let mut conn = pool.get_conn()?;
    let rows = conn.query_map(
        "SELECT StudentID FROM Students",
        |student_id: i32| (student_id, "OUT".to_string()),
    )?;
    for (student_id, status) in rows {
        student_statuses.insert(student_id, status);
    }
    Ok(student_statuses)
}

pub fn insert_log_record(
    student_id: i32,
    reason: &str,
    expected: i32,
) -> Result<(), Box<dyn Error>> {
    let opts = Opts::from_url(&format!(
        "mysql://{}:{}@{}:{}/{}",
        DB_USERNAME, DB_PASSWORD, DB_HOST, DB_PORT, DB_NAME
    ))?;
    let mut pool = mysql::Pool::new(opts)?;
    let mut conn = pool.get_conn()?;
    let timestamp = Utc::now().naive_utc();
    let formatted_timestamp = format!("{}", timestamp);
    conn.exec_drop(
        "INSERT INTO Logs (Time, StudentID, Reason, Expected) VALUES (?, ?, ?, ?)",
        (formatted_timestamp, student_id, reason, expected),
    )?;
    Ok(())
}
