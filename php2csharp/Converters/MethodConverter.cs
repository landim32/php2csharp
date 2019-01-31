using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Text.RegularExpressions;
using System.Threading.Tasks;

namespace PHP2CSharp.Converters
{
    public class MethodConverter : BaseConverter
    {
        private const string METHOD_PARAM_DOC = @"\@param\s*([0-9,a-z,A-Z,_,|,\[,\]]+)\s*\$([0-9,a-z,A-Z,_,|]+)";
        private const string METHOD_PARAM = @"([0-9,a-z,A-Z,_]+|)\s*\$([0-9,a-z,A-Z,_]+)";
        private const string METHOD_RETURN = @"\@return\s*([0-9,a-z,A-Z,_,|,\[,\]]+)\s*";
        private const string METHOD_FULL = @"/\*\*(?s:(?!\*/).)*\*/\s*(public|private|protected)\s*function\s*([0-9,a-z,A-Z,_]+)\((.*?)\)";
        private const string PARAM_ITEM = @"([0-9,a-z,A-Z,_]+|)\s*\$([0-9,a-z,A-Z,_]+)";

        public override string convert(string sourceCode)
        {
            sourceCode = Regex.Replace(sourceCode, METHOD_FULL, delegate (Match m1) {
                var returnType = "void";
                var docs = "";
                //var docs = m1.Groups[1].Value;
                var funcType = m1.Groups[1].Value;
                var funcName = m1.Groups[2].Value;
                var funcParam = m1.Groups[3].Value;
                var m = Regex.Match(m1.Groups[0].Value, @"\*\*(.*?)\*/", RegexOptions.IgnoreCase | RegexOptions.Singleline);
                if (m.Success)
                {
                    docs = m.Groups[1].Value.Trim();
                }


                var m2 = Regex.Match(docs, METHOD_RETURN, RegexOptions.IgnoreCase);
                if (m2.Success)
                {
                    returnType = getTrueType(m2.Groups[1].Value);
                }

                var paramsArg = new Dictionary<string, string>();
                var paramsDef = new Dictionary<string, string>();
                foreach (var param in funcParam.Split(',')) {
                    var m5 = Regex.Match(param, PARAM_ITEM);
                    if (m5.Success) {
                        paramsArg.Add(m5.Groups[2].Value, getTrueType(m5.Groups[3].Value));
                        var m6 = Regex.Match(param, @"=\s*(.*)");
                        if (m6.Success) {
                            paramsDef.Add(m5.Groups[2].Value, m6.Groups[1].Value);
                        }
                    }
                }

                /*
                Regex regex = new Regex(METHOD_PARAM);
                foreach (Match m4 in regex.Matches(funcParam))
                {
                    if (m4.Success && !paramsArg.ContainsKey(m4.Groups[2].Value))
                    {
                        paramsArg.Add(m4.Groups[2].Value, getTrueType(m4.Groups[1].Value));
                    }
                }
                */

                //var paramsDoc = new Dictionary<string, string>();
                Regex regex = new Regex(METHOD_PARAM_DOC);
                foreach (Match m3 in regex.Matches(docs))
                {
                    if (m3.Success && paramsArg.ContainsKey(m3.Groups[2].Value))
                    {
                        paramsArg[m3.Groups[2].Value] = getTrueType(m3.Groups[1].Value);
                    }
                }

                /*
                foreach (var p in paramsArg)
                {
                    if (paramsArg.ContainsKey(p.Key) && string.IsNullOrEmpty(paramsArg[p.Key]))
                    {
                        paramsArg[p.Key] = getTrueType(p.Value);
                    }
                }
                */

                string paramsStr = string.Join(", ", paramsArg.Select(p => {
                    string str = "";
                    if (!string.IsNullOrEmpty(p.Value)) {
                        str += p.Value + " ";
                    }
                    str += p.Key;
                    if (paramsDef.ContainsKey(p.Key)) {
                        str += " = " + paramsDef[p.Key];
                    }
                    return str;
                }).ToArray());

                return funcType + " " + returnType + " " + funcName + "(" + paramsStr + ")";
            }, RegexOptions.IgnoreCase | RegexOptions.Singleline);
            return sourceCode;
        }
    }
}
