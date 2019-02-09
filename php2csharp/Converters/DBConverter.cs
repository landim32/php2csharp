using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Text.RegularExpressions;
using System.Threading.Tasks;

namespace PHP2CSharp.Converters
{
    public class DBConverter : BaseConverter
    {
        private const string PREPARE_QUERY = @"\$([0-9,a-z,A-Z,_]+)\s*=\s*DB::getDB\(\)->prepare\(\$([0-9,a-z,A-Z,_]+)\);";
        private const string RETURN_LIST = @"return\s*DB::getResult\(\$([0-9,a-z,A-Z,_]+),[""|'][0-9,a-z,A-Z,_,\\]+\\([0-9,a-z,A-Z,_]+)[""|']\);";
        private const string RETURN_GET = @"return\s*DB::getValueClass\(\$([0-9,a-z,A-Z,_]+),\s*[""|'][0-9,a-z,A-Z,_,\\]+\\([0-9,a-z,A-Z,_]+)[""|']\);";
        private const string RETURN_INSERTED_ID = @"\$([0-9,a-z,A-Z,_]+)->execute\(\);\s*return\s*DB::lastInsertId\(\);";
        private const string EXECUTE = @"\$([0-9,a-z,A-Z,_]+)->execute\(\);";
        private const string BIND_VALUE_INT = @"\$([0-9,a-z,A-Z,_]+)->bindValue\([""|']:([0-9,a-z,A-Z,_]+)[""|'],\s*(.*?),\s*(.*?)\);";
        private const string BIND_VALUE_STR = @"\$([0-9,a-z,A-Z,_]+)->bindValue\([""|']:([0-9,a-z,A-Z,_]+)[""|'],\s*(.*?)\);";
        private const string QUERY_FULL = @"\$query\s*=\s*[""|'](.*?)[""|'];";
        private const string QUERY_PLUS = @"\$query\s*\.=\s*[""|'](.*?)[""|'];";
        private const string QUERY_END = @"\$query\s*=\s*\$this->query\(\)\s*.\s*[""|'](.*?)[""|'];";

        private string doReallyTrim(string text) {
            text = text.Replace("\r", "");
            text = text.Replace("\n", "");
            text = text.Replace(" ", "");
            return text.Trim();
        }

        private string convertQuery(string sourceCode)
        {
            sourceCode = Regex.Replace(sourceCode, QUERY_FULL, delegate (Match m1) {
                var str = "var query = @\"";
                str += Regex.Replace(m1.Groups[1].Value, ":([0-9,a-z,A-Z,_]+)", delegate (Match m2) {
                    return "@" + m2.Groups[1].Value;
                }, RegexOptions.IgnoreCase | RegexOptions.Singleline);
                str += "\";";
                return str;
            }, RegexOptions.IgnoreCase | RegexOptions.Singleline);
            sourceCode = Regex.Replace(sourceCode, QUERY_PLUS, delegate (Match m1) {
                var str = "var query += @\"";
                str += Regex.Replace(m1.Groups[1].Value, ":([0-9,a-z,A-Z,_]+)", delegate (Match m2) {
                    return "@" + m2.Groups[1].Value;
                }, RegexOptions.IgnoreCase | RegexOptions.Singleline);
                str += "\";";
                return str;
            }, RegexOptions.IgnoreCase | RegexOptions.Singleline);
            sourceCode = Regex.Replace(sourceCode, QUERY_END, delegate (Match m1) {
                var str = "var query = this.query() + @\"";
                str += Regex.Replace(m1.Groups[1].Value, ":([0-9,a-z,A-Z,_]+)", delegate (Match m2) {
                    return "@" + m2.Groups[1].Value;
                }, RegexOptions.IgnoreCase | RegexOptions.Singleline);
                str += "\";";
                return str;
            }, RegexOptions.IgnoreCase | RegexOptions.Singleline);
            return sourceCode;
        }

        private string convertList(string sourceCode) {
            string pattern = PREPARE_QUERY + @"\s*(.*?)\s" + RETURN_LIST;
            //string pattern = PREPARE_QUERY;
            //string pattern = RETURN_LIST;
            sourceCode = Regex.Replace(sourceCode, pattern, delegate (Match match) {
                //return match.Groups[1].Value + ".Parameters.Add(\"" + match.Groups[2].Value + "\", MySqlDbType.Int32).Value = " + match.Groups[3].Value;
                var cmdVar = match.Groups[1].Value;
                var queryVar = match.Groups[2].Value;
                var paramsCode = match.Groups[3].Value;
                var typeName = match.Groups[5].Value;
                string retorno = "return new DB<" + typeName + ">().list(" + queryVar + ", lerInfo";
                if (!string.IsNullOrEmpty(doReallyTrim(paramsCode))) {
                    retorno += ", (" + cmdVar + ") => {\r\n";
                    retorno += paramsCode;
                    retorno += "\r\n}";
                }
                retorno += ");";
                return retorno;
                /*
                return new DB<BairroInfo>().list(this.query(), lerBairro, (cmd) => {
                    cmd.Parameters.Add("id_cidade", MySqlDbType.Int32).Value = id_cidade;
                });
                 */
            }, RegexOptions.IgnoreCase | RegexOptions.Singleline);
            return sourceCode;
        }

        private string convertGet(string sourceCode)
        {
            string pattern = PREPARE_QUERY + @"\s*(.*?)\s" + RETURN_GET;
            sourceCode = Regex.Replace(sourceCode, pattern, delegate (Match match) {
                var cmdVar = match.Groups[1].Value;
                var queryVar = match.Groups[2].Value;
                var paramsCode = match.Groups[3].Value;
                var typeName = match.Groups[5].Value;
                string retorno = "return new DB<" + typeName + ">().get(" + queryVar + ", lerInfo";
                if (!string.IsNullOrEmpty(doReallyTrim(paramsCode)))
                {
                    retorno += ", (" + cmdVar + ") => {\r\n";
                    retorno += paramsCode;
                    retorno += "\r\n}";
                }
                retorno += ");";
                return retorno;
            }, RegexOptions.IgnoreCase | RegexOptions.Singleline);
            return sourceCode;
        }

        private string convertInsertWithId(string sourceCode)
        {
            string pattern = PREPARE_QUERY + @"\s*(.*?)\s" + RETURN_INSERTED_ID;
            sourceCode = Regex.Replace(sourceCode, pattern, delegate (Match match) {
                var cmdVar = match.Groups[1].Value;
                var queryVar = match.Groups[2].Value;
                var paramsCode = match.Groups[3].Value;

                string retorno = "return (int) new DB<object>().executeAndGetId(" + queryVar;
                if (!string.IsNullOrEmpty(doReallyTrim(paramsCode)))
                {
                    retorno += ", (" + cmdVar + ") => {\r\n";
                    retorno += paramsCode;
                    retorno += "\r\n}";
                }
                retorno += ");";
                return retorno;
            }, RegexOptions.IgnoreCase | RegexOptions.Singleline);
            return sourceCode;
        }

        private string convertExecute(string sourceCode)
        {
            string pattern = PREPARE_QUERY + @"\s*(.*?)\s" + EXECUTE;
            sourceCode = Regex.Replace(sourceCode, pattern, delegate (Match match) {
                var cmdVar = match.Groups[1].Value;
                var queryVar = match.Groups[2].Value;
                var paramsCode = match.Groups[3].Value;

                string retorno = "new DB<object>().execute(" + queryVar;
                if (!string.IsNullOrEmpty(doReallyTrim(paramsCode)))
                {
                    retorno += ", (" + cmdVar + ") => {\r\n";
                    retorno += paramsCode;
                    retorno += "\r\n}";
                }
                retorno += ");";
                return retorno;
            }, RegexOptions.IgnoreCase | RegexOptions.Singleline);
            return sourceCode;
        }

        public override string convert(string sourceCode)
        {
            sourceCode = convertQuery(sourceCode);
            sourceCode = convertList(sourceCode);
            sourceCode = convertGet(sourceCode);
            sourceCode = convertInsertWithId(sourceCode);
            sourceCode = convertExecute(sourceCode);
            sourceCode = sourceCode.Replace("PDOStatement", "MySqlCommand");
            sourceCode = Regex.Replace(sourceCode, BIND_VALUE_INT, delegate (Match match) {
                return match.Groups[1].Value + ".Parameters.Add(\"" + match.Groups[2].Value + "\", MySqlDbType.Int32).Value = " + match.Groups[3].Value + ";";
            }, RegexOptions.IgnoreCase);
            sourceCode = Regex.Replace(sourceCode, BIND_VALUE_STR, delegate (Match match) {
                return match.Groups[1].Value + ".Parameters.Add(\"" + match.Groups[2].Value + "\", MySqlDbType.VarChar).Value = " + match.Groups[3].Value + ";";
            }, RegexOptions.IgnoreCase);
            return sourceCode;
        }
    }
}
